<?php

namespace App\V3\Services\Queue\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;

class Connector {

    const ZMQ_CONNECTOR = 'zmq';
    const RABBIT_CONNECTOR = 'rabbit';

    protected static $connectors = [
        self::ZMQ_CONNECTOR => ZmqConnector::class,
        self::RABBIT_CONNECTOR => RabbitConnector::class
    ];

    /**
     * @var string
     */
    protected static $activeConnector = self::RABBIT_CONNECTOR;

    /**
     * @param string $name
     * @return mixed|null
     */
    public static function getConnector(string $name) {
        return static::$connectors[$name] ?? null;
    }

    /**
     * @return mixed|null
     */
    public static function getActiveConnector() {
        return static::getConnector(static::$activeConnector);
    }

    /**
     *
     * @param string $connectorClass
     * @param array $params
     * @return mixed
     */
    public static function staticConnect(string $connectorClass, array $params) {
        return $connectorClass::staticConnect($params);
    }
}