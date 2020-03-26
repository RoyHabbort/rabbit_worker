<?php

namespace App\V3\Services\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Monolog\Logger;
use App\Registry;
use App\V3\Helpers\LoggersManager;
use App\V3\Services\Queue\Callback\RabbitCallback;
use App\V3\Services\Rabbit\RabbitLogger;
use App\V3\Traits\ServiceLoggerTrait;

class WorkerRabbit implements WorkerInterface {

    use ServiceLoggerTrait;

    /**
     * небольшой хак, чтобы определялись методы RabbitQueue
     * @param RabbitQueue $queue
     */
    public function daemon(QueueContract $queue) {

        $connection = $queue->getConnection();
        $channel = $queue->getChannel($connection, $queue->getConnectionName());

        $rabbitLogger = new RabbitLogger($this->getLogger(), $queue->getConnectionName());

        try {
            $channel->basic_consume(
                $queue->getConnectionName(),
                '',
                false,
                false,
                false,
                false,
                RabbitCallback::getCallback($rabbitLogger)
            );
            $queue->listen($channel);
        }
        catch (FailedQueueWork $exception) {
            $this->logError('Queue work not normal. ' . $exception->getMessage());
        }
        catch (\Exception $exception) {
            $this->logError($exception->getMessage());
        }

        //подтверждение доставки будет ждать до тех пор, пока конект не закрыт.
        //если ошибка, и соединение не закрыто, будет ждать долго
        $queue->closeConnection($connection, $channel);
        if (!empty($exception)) {
            throw new QueueRestartSignal($exception->getMessage(), $exception->getCode(), $exception);
        }
        throw new QueueRestartSignal();
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
}