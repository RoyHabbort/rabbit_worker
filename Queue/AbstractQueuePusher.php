<?php

namespace App\V3\Services\Queue;


abstract class AbstractQueuePusher {

    protected static function push($action, $payload) {
        QueueFacade::push(QueueOptions::RABBIT_QUEUE, static::getJob(), [
            'action' => $action,
            'payload' => $payload
        ]);
    }

    protected static abstract function getJob() : RabbitJobCommandInterface;

}