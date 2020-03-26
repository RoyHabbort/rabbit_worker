<?php

namespace App\V3\Services\Queue;

use Illuminate\Contracts\Queue\Queue;
use App\V3\Services\Queue\Connectors\Connector;
use App\V3\Services\Rabbit\RabbitOptionsFacade;

class QueuesManager {

    const DEFAULT_PREFIX = 'que_';

    /** @var string  */
    protected $prefix = self::DEFAULT_PREFIX;

    /** @var array  */
    protected $queues = [];

    protected static $instance;

    public static function getInstance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct() {
    }

    private function __clone() {
    }

    private function __wakeup() {
    }

    /**
     * @param string $name
     * @param Queue $queue
     * @return QueuesManager
     */
    public function registerQueue(string $name, Queue $queue) : QueuesManager {
        $hash = $this->getHash($name);
        $this->queues[$hash] = $queue;
        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHash(string $name) : string {
        return md5($this->prefix . $name);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws QueueException
     */
    public function getQueue(string $name) {
        $hash = $this->getHash($name);
        if (empty($this->queues[$hash])) {
            $connectorClass = Connector::getActiveConnector();

            $options = RabbitOptionsFacade::getEnvironmentOptions();
            if (empty($options)) {
                throw new QueueException('Queue with name {' . $name . '} not regitred');
            }

            $this->queues[$hash] = Connector::staticConnect($connectorClass, [
                'options' => $options,
                'connectionName' => $name
            ]);
        }

        return $this->queues[$hash];
    }
}