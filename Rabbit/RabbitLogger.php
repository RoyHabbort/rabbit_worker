<?php

namespace App\V3\Services\Rabbit;

use Monolog\Logger;
use App\Models\WorkerLogs;
use App\V3\Helpers\LoggerTypeHelper;
use App\V3\Traits\ServiceLoggerTrait;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitLogger {

    use ServiceLoggerTrait;

    CONST LOG_RESULT_DEFAULT = 0;
    CONST LOG_RESULT_SUCCESS = 1;
    CONST LOG_RESULT_ERROR = 2;

    /** @var Logger  */
    protected $logger;
    /** @var WorkerLogs  */
    protected $loggerDb;
    /** @var string  */
    protected $queueName;
    /** @var  AMQPMessage */
    protected $message;

    public function __construct(Logger $logger, string $queueName) {
        $this->logger = $logger;
        $this->loggerDb = new WorkerLogs();
        $this->queueName = $queueName;
    }

    /**
     * @param $message
     * @return bool
     */
    public function logError($message) {
        $message .= ' || MESSAGE: ' . $this->message->body;
        return $this->log($message, LoggerTypeHelper::ERROR_LOGGER);
    }

    /**
     * @param string $message
     * @return bool
     */
    public function logWarning(string $message) {
        $message .= ' || MESSAGE: ' . $this->message->body;
        return $this->log($message, LoggerTypeHelper::WARNING_LOGGER);
    }

    /**
     * это временные решения на время отладки
     * @param string $jobName
     * @param string $workerParamsJson
     * @param array $jobParams
     * @param int $result
     * @param string $errorText
     * @return int
     */
    public function logInDb(
        string $jobName,
        string $workerParamsJson,
        array $jobParams,
        int $result,
        string $errorText = ''
    ) {

        $data = [
            'worker' => $this->queueName,
            'job' => $jobName,
            'worker_params' => $workerParamsJson,
            'job_params' => json_encode($jobParams),
            'result' => $result,
            'error_text' => $errorText
        ];

        return $this->loggerDb->add($data);
    }

    /**
     * @param AMQPMessage $message
     * @return $this
     */
    public function setMessage(AMQPMessage $message) {
        $this->message = $message;
        return $this;
    }
}