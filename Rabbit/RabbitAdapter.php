<?php

namespace App\V3\Services\Rabbit;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitAdapter implements RabbitInterface {

    /** @var  string */
    protected $connectionName;
    /** @var  string */
    protected $host;
    /** @var  string */
    protected $port;
    /** @var  string */
    protected $user;
    /** @var  string */
    protected $password;
    /** @var bool  */
    protected $passive = false;
    /** @var bool */
    protected $durable = true;
    /** @var bool */
    protected $exclusive = false;
    /** @var bool */
    protected $autoDelete = false;

    /**
     * RabbitAdapter constructor.
     * @param RabbitConfig $config
     * @param string $connectionName
     */
    public function __construct(RabbitConfig $config, string $connectionName) {
        $this->host = $config->getHost();
        $this->port = $config->getPort();
        $this->user = $config->getUser();
        $this->password = $config->getPassword();
        $this->connectionName = $connectionName;

        if (!empty($config->passive)) {
            $this->passive = $config->passive;
        }

        if (!empty($config->durable)) {
            $this->durable = $config->durable;
        }

        if (!empty($config->exclusive)) {
            $this->exclusive = $config->exclusive;
        }

        if (!empty($config->autoDelete)) {
            $this->autoDelete = $config->autoDelete;
        }
    }

    /**
     * @return AMQPStreamConnection
     */
    public function getConnection() : AMQPStreamConnection {
        return new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
    }

    /**
     * @param AMQPStreamConnection $connect
     * @param string $queueName
     * @return AMQPChannel
     */
    public function getChannel(AMQPStreamConnection $connect, string $queueName) : AMQPChannel {
        $channel = $connect->channel();
        //не давать работнику больше 1 сообщения. пока он его не обработает
        $channel->basic_qos(null, 1, null);
        $channel->queue_declare($queueName, $this->passive, $this->durable, $this->exclusive, $this->autoDelete);
        return $channel;
    }

    /**
     * @param AMQPStreamConnection $connect
     * @param AMQPChannel $channel
     */
    public function closeConnect(AMQPStreamConnection $connect, AMQPChannel $channel) {
        $channel->close();
        $connect->close();
    }

    /**
     * @param AMQPChannel $channel
     * @param string $message
     * @param string $queueName
     */
    public function send(AMQPChannel $channel, string $message, string $queueName) {
        $msg = new AMQPMessage($message, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($msg, '', $queueName);
    }

    /**
     * @return string
     */
    public function getConnectionName(): string {
        return $this->connectionName;
    }
}