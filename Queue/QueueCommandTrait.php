<?php

namespace App\V3\Services\Queue;

trait QueueCommandTrait {

    protected $queueManager;

    public function getQueue(string $queueName) {
        return QueueFacade::getQueue($queueName);
    }

}