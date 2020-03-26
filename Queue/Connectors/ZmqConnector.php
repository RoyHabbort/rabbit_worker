<?php

namespace App\V3\Services\Queue\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\NullQueue;
use App\V3\Services\Queue\QueueException;
use App\V3\Services\Queue\ZmqQueue;
use App\V3\Services\Zmq\FactoryZmq;
use App\V3\Services\Zmq\ZmqAdapter;

class ZmqConnector implements ConnectorInterface {

    /** @var  \ZMQContext */
    protected $context;
    /** @var  integer */
    protected $typeConnection;

    public function __construct(int $typeConnection) {
        $this->typeConnection = $typeConnection;
    }

    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return ZmqQueue
     * @throws QueueException
     */
    public function connect(array $config) {
        $context = $this->getContext();

        $connection = $config['connection'] ?? '';
        if (empty($connection)) {
            throw new QueueException('Empty connection in config');
        }

        try {
            $adapter = new ZmqAdapter($context, $connection, $this->typeConnection);
        }
        catch (\ZMQException $exception) {
            throw new QueueException($exception->getMessage());
        }

        return new ZmqQueue($adapter);
    }

    public function getContext() {
        return $this->context ?? FactoryZmq::getContext();
    }

    /**
     * @param array $params
     * @return \Illuminate\Contracts\Queue\Queue
     * @throws QueueException
     */
    public static function staticConnect(array $params) {
        if (empty($params) || empty($params['type']) || empty($params['options'])) {
            throw new QueueException('Don`t exist params dor create connect to zmq queue');
        }
        $connector = new self($params['type']);
        return $connector->connect($params['options']);
    }
}