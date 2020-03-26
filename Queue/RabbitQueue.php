<?php

namespace App\V3\Services\Queue;

use Illuminate\Queue\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use App\V3\Services\Rabbit\RabbitException;
use App\V3\Services\Rabbit\RabbitInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitQueue extends Queue implements QueueContract{

    /** @var RabbitInterface  */
    protected $rabbit;

    public function __construct(RabbitInterface $rabbit) {
        $this->rabbit = $rabbit;
    }

    public function size($queue = null) {
        return null;
    }

    /**
     * @param object|string $job
     * @param string $data
     * @param null $queue
     * @return mixed
     * @throws RabbitException
     */
    public function push($job, $data = '', $queue = null) {
        if (empty($queue)) {
            throw new RabbitException('Empty queueName');
        }
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * @param string $payload
     * @param null $queue
     * @param array $options
     * @return $this
     * @throws RabbitException
     */
    public function pushRaw($payload, $queue = null, array $options = []) {
        if (empty($queue)) {
            throw new RabbitException('Empty queueName');
        }

        $connection = $this->rabbit->getConnection();
        $channel = $this->rabbit->getChannel($connection, $queue);

        $this->rabbit->send($channel, $payload, $queue);

        $this->rabbit->closeConnect($connection, $channel);

        return $this;
    }

    public function later($delay, $job, $data = '', $queue = null) {
        return null;
    }

    /**
     * @param null $queue
     * @return null
     */
    public function pop($queue = null) {
        return null;
    }

    /**
     * @return string
     */
    public function getConnectionName() {
        return $this->rabbit->getConnectionName();
    }

    /**
     * @param string $job товарищи, может я чего не знаю, но какого хрена строка проверяется на is_object у родителя?
     * @param string $data
     * @return string
     */
    protected function createPayload($job, $data = '') {
        return json_encode($job->payload());
    }

    /**
     * @param AMQPChannel $channel
     */
    public function listen(AMQPChannel $channel) {
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * @return AMQPStreamConnection
     */
    public function getConnection() {
        return $this->rabbit->getConnection();
    }

    /**
     * @param AMQPStreamConnection $connect
     * @param string $queueName
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    public function getChannel(AMQPStreamConnection $connect, string $queueName) {
        return $this->rabbit->getChannel($connect, $queueName);
    }

    /**
     * @param AMQPStreamConnection $connection
     * @param AMQPChannel $channel
     */
    public function closeConnection(AMQPStreamConnection $connection, AMQPChannel $channel) {
        $this->rabbit->closeConnect($connection, $channel);
    }
}