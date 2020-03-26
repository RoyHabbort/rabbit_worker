<?php

namespace App\V3\Services\Rabbit;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class FactoryRabbit {

    public static function getContext(string $host, string $port, string $user, string $password) {
        return new AMQPStreamConnection($host, $port, $user, $password);
    }
}