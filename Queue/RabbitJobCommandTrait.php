<?php

namespace App\V3\Services\Queue;

trait RabbitJobCommandTrait {

    public static function getSignature() : string {
        return static::$signature;
    }

}