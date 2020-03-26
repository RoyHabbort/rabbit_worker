<?php

namespace App\V3\Services\Queue;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\DetectsLostConnections;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Monolog\Logger;
use App\CacheFactory;
use App\Models\WorkerLogs;
use App\Registry;
use App\V3\Console\Jobs\Core\JobException;
use App\V3\Console\Jobs\Core\JobRegister;
use App\V3\Helpers\LoggersManager;
use App\V3\Helpers\LoggerTypeHelper;
use App\V3\Services\Cache\CacheAbstract;
use App\V3\Traits\ServiceLoggerTrait;

class WorkerZmq implements WorkerInterface {

    use DetectsLostConnections, ServiceLoggerTrait;

    CONST LOG_RESULT_DEFAULT = 0;
    CONST LOG_RESULT_SUCCESS = 1;
    CONST LOG_RESULT_ERROR = 2;

    const DEFAULT_MAX_TRIES = 3;

    /** @var int Если произошла ошибка, то задача повторно выполнится через 5 минут */
    const DEFAULT_TIMEOUT= 300;

    /** @var int  */
    protected $pause = 5000;

    /** @var int  */
    protected $startProcessTime;
    /** @var CacheAbstract */
    protected $cache;
    /** @var int  */
    protected $defaultTimeout = self::DEFAULT_TIMEOUT;
    /** @var WorkerLogs */
    protected $loggerDb;
    /** @var string  */
    protected $currentMessage = '';

    public function __construct() {
        $this->startProcessTime = time();
        $this->cache = CacheFactory::getInstance()->get('memcached');
        $this->loggerDb = new WorkerLogs();
    }

    /**
     * @param QueueContract $queue
     * @throws QueueRestartSignal
     * @deprecated После переноса на RABBIT не актуально
     */
    public function daemon(QueueContract $queue) {

        //  Process tasks forever
        while ($jsonData = $queue->pop()) {
            $this->currentMessage = $jsonData;

            //вначале, из-за пары континиумов.
            usleep($this->getPause());

            $data = json_decode($jsonData, true);

            if (empty($data) || empty($data['job']) || empty($data['queue'])) {
                //логируем несуразность, и забываем о ней.
                $this->logWarning('Incorrect enter params for job: {' . $jsonData . '}');
                $this->logInDb($queue, 'incorrect_params', $jsonData, [], static::LOG_RESULT_ERROR, 'Incorrect enter params for job');
                if (!$this->isRestart()) {
                    continue;
                }
            }

            $job = $data['job'];
            $params = $data['params'] ?? [];
            $attempts = $data['attempts'] ?? 0;
            $timeoutTo = $data['timeoutTo'] ?? 0;
            $timeStart = $data['timeStart'] ?? 0;
            $maxTries = $data['maxTries'] ?? static::DEFAULT_MAX_TRIES;
            $queueName = $data['queue'];

            try {
                //получаем класс исполнителя
                $executorClassName = JobRegister::get($job);
                $executor = new $executorClassName();
                //!WARNING! А ты, зарегистрировал свою работу?!

                //если время выполнения ещё не наступило
                if ($timeoutTo > time()) {
                    //то не выполняем данную задачу, а снова шлём в очередь
                    QueueFacade::push($executor, $params, $attempts, $maxTries, $timeoutTo - $timeStart, $timeStart);
                    if (!$this->isRestart()) {
                        continue;
                    }
                }

                $currentQueueName = QueueOptions::getQueueNameByConnection($queue->getConnectionName());

                // Если это воркер глобальной очереди,
                // то он должен перенаправлять задачи, остальным очередям,
                // а не выполнять их сам.
                if ($currentQueueName == QueueFacade::getGlobalQueueName()) {
                    $queueDestination = $this->getDelegateDestination($executor);
                    QueueFacade::pushToParticular($queueDestination, $executor, $params, $attempts, $maxTries);
                    if (!$this->isRestart()) {
                        continue;
                    }
                }
            }
            //!!WARNING!! Важно, чтобы не перехватывался QueueRestartSignal. А он наследуется от QueueExceptions
            catch (JobException $exception) {
                //логируем и забываем
                $this->logWarning($exception->getMessage());
                $this->logInDb($queue, 'early_log', $jsonData, $params, static::LOG_RESULT_ERROR, $exception->getMessage());
                if (!$this->isRestart()) {
                    continue;
                }
            }

            try {
                //Если не пришло подтверждения о выполнении работы, то значит работа не выполнена
                if (!$executor->handle($params)) {
                    throw new QueueException('Work not return success signal');
                }
                $this->logInDb($queue, $executorClassName, $jsonData, $params, static::LOG_RESULT_SUCCESS);
            }
            catch(\Throwable $throwable) {
                //логируем и отправляем снова в глобальную очередь. если более 3х попыток, забываем.
                if ($attempts >= $maxTries) {
                    $this->logError('Limit max tries. Error : ' . $throwable->getMessage());
                    $this->logInDb($queue, $executorClassName, $jsonData, $params, static::LOG_RESULT_ERROR, 'Limit max tries. Error : ' . $throwable->getMessage());
                    if (!$this->isRestart()) {
                        continue; //ничего не делаем, а соответственно забываем о работе.
                    }
                }

                $this->logError($throwable->getMessage());
                $this->logInDb($queue, $executorClassName, $jsonData, $params, static::LOG_RESULT_ERROR, $throwable->getMessage());
                //@todo: реализовать задержку для повторной отправки. и чтобы очередь не задерживала
                //только повторная отправка в очередь
                $attempts++;
                QueueFacade::push($executor, $params, $attempts, $maxTries, $this->defaultTimeout);
            }
            $this->isRestart();
        }
    }

    /**
     * @return bool
     * @throws QueueRestartSignal
     */
    protected function isRestart() {
        //сигнал мягкой остановки
        $restartTime = $this->cache->get('queue_restart_signal');
        if (!empty($restartTime) && $this->startProcessTime < $restartTime) {
            throw new QueueRestartSignal(); //завершаем процесс
        }
        return false;
    }

    /**
     * @return int
     */
    public function getPause(): int {
        return $this->pause;
    }

    /**
     * @param int $pause
     * @return WorkerZmq
     */
    public function setPause(int $pause): WorkerZmq {
        $this->pause = $pause;
        return $this;
    }

    /**
     * @param ZmqJobCommandInterface $executor
     * @return string
     */
    protected function getDelegateDestination(ZmqJobCommandInterface $executor) : string {
        $allowedQueues = $executor::getAllowedQueues();

        return !empty($allowedQueues)
            ? array_random($allowedQueues)
            : QueueOptions::getRandomWorker();
    }

    /**
     * @return Logger
     */
    public function getLogger() : Logger{
        if (empty($this->logger)) {
            $name = str_replace('\\', '_', get_called_class());
            $debugMode = Registry::get('debug_console_mode');
            $this->logger = LoggersManager::getLogger($name, !empty($debugMode));
        }

        return $this->logger;
    }

    /**
     * это временные решения на время отладки
     * @param QueueContract $queue
     * @param string $jobName
     * @param string $workerParamsJson
     * @param array $jobParams
     * @param int $result
     * @param string $errorText
     * @return int
     */
    protected function logInDb(
        Queue $queue,
        string $jobName,
        string $workerParamsJson,
        array $jobParams,
        int $result,
        string $errorText = ''
    ) {

        $data = [
            'worker' => QueueOptions::getQueueNameByConnection($queue->getConnectionName()),
            'job' => $jobName,
            'worker_params' => $workerParamsJson,
            'job_params' => json_encode($jobParams),
            'result' => $result,
            'error_text' => $errorText
        ];

        return $this->loggerDb->add($data);
    }

    /**
     * @param $message
     * @return bool
     */
    public function logError($message) {
        $message .= ' || MESSAGE: ' . $this->currentMessage;
        return $this->log($message, LoggerTypeHelper::ERROR_LOGGER);
    }

    /**
     * @param $message
     * @return bool
     */
    public function logWarning($message) {
        $message .= ' || MESSAGE: ' . $this->currentMessage;
        return $this->log($message, LoggerTypeHelper::WARNING_LOGGER);
    }
}