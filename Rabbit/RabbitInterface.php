<?php

namespace App\V3\Services\Rabbit;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

interface RabbitInterface {

    public function getConnection() : AMQPStreamConnection;

    public function getChannel(AMQPStreamConnection $connect, string $queueName) : AMQPChannel;

    public function send(AMQPChannel $channel, string $message, string $queueName);

    public function closeConnect(AMQPStreamConnection $connect, AMQPChannel $channel);

    public function getConnectionName() : string;
}