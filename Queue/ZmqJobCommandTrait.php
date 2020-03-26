<?php

namespace App\V3\Services\Queue;

trait ZmqJobCommandTrait {

    public static function getSignature() : string {
        return static::$signature;
    }

    public static function getAllowedQueues() : array {
        return static::$allowedQueues ?? [];
    }

}