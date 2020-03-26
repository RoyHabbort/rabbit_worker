<?php

namespace App\V3\Services\Queue;

use App\V3\Services\Queue\Jobs\RabbitJob;
use App\V3\Services\Queue\Jobs\ZmqJob;

class QueueFacade {

    /**
     * @return QueuesManager
     */
    public static function getManager() : QueuesManager {
        return QueuesManager::getInstance();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function getQueue(string $name) {
        return static::getManager()->getQueue($name);
    }

    /**
     * @param string $queueName
     * @param RabbitJobCommandInterface $jobCommand
     * @param array $params
     * @param int $attempt
     * @param int $maxTries
     * @param int $timeoutTo
     * @param int $timeStart
     */
    public static function push(
        string $queueName,
        RabbitJobCommandInterface $jobCommand,
        array $params = [],
        int $attempt = 0,
        int $maxTries = RabbitJob::DEFAULT_MAX_TRIES,
        int $timeoutTo = 0,
        int $timeStart = 0
    ) {
        $queue = static::getQueue($queueName);

        $signature = $jobCommand::getSignature();
        $params['queue'] = $queueName;
        $job = new RabbitJob($signature, $queue, $params, $attempt, $maxTries, $timeoutTo, $timeStart);

        $queue->push($job, '', $queueName);
    }

    /**
     * @param string $queueName
     * @param string $message
     */
    public static function pushRaw(string $queueName, string $message) {
        $queue = static::getQueue($queueName);
        $queue->pushRaw($message, $queueName);
    }
}