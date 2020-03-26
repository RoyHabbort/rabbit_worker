<?php

namespace App\V3\Services\Queue\Connectors;

use App\V3\Services\Queue\QueueException;
use App\V3\Services\Queue\RabbitQueue;
use App\V3\Services\Rabbit\RabbitAdapter;
use App\V3\Services\Rabbit\RabbitConfig;
use App\V3\Services\Rabbit\RabbitException;

class RabbitConnector {

    /**
     * @param RabbitConfig $config
     * @param string $connectionName
     * @return RabbitQueue
     * @throws QueueException
     */
    public function connect(RabbitConfig $config, string $connectionName) {

        if (empty($config) || empty($connectionName)) {
            throw new QueueException('Empty connection in config');
        }

        try {
            $rabbitAdapter = new RabbitAdapter($config, $connectionName);
        }
        catch (RabbitException $exception) {
            throw new QueueException($exception->getMessage());
        }

        return new RabbitQueue($rabbitAdapter);
    }

    /**
     * @param array $params
     * @return RabbitQueue
     */
    public static function staticConnect(array $params = []) {
        $connector = new self();
        return $connector->connect($params['options'], $params['connectionName']);
    }
}