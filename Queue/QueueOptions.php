<?php

namespace App\V3\Services\Queue;

class QueueOptions {

    const RABBIT = 'rabbit';
    const ZMQ = 'zmq';

    const DEFAULT_QUEUE = 'default';
    const GLOBAL_QUEUE = 'global';
    const WORKER1_QUEUE = 'worker1';
    const WORKER2_QUEUE = 'worker2';
    const WORKER3_QUEUE = 'worker3';
    const ZMQ_PROTOCOL_ITEMS = 'items';
    const ZMQ_PROTOCOL_MESSAGES = 'message';

    const RABBIT_QUEUE = 'rabbit_worker';

    public static $config = [
        self::ZMQ => [
            self::DEFAULT_QUEUE => [
            ],
            self::GLOBAL_QUEUE => [
            ],
            self::WORKER1_QUEUE => [
            ],
            self::WORKER2_QUEUE => [
            ],
            self::WORKER3_QUEUE => [
            ],
            self::ZMQ_PROTOCOL_ITEMS => [
            ],
            self::ZMQ_PROTOCOL_MESSAGES => [
            ],
        ],
        self::RABBIT => [
        ]
    ];

    /**
     * @var array
     */
    public static $workers = [
        self::WORKER1_QUEUE,
        self::WORKER2_QUEUE,
        self::WORKER3_QUEUE
    ];

    /**
     * @var string
     */
    protected static $defaultQueueType = self::RABBIT;

    /**
     * @param string $queue
     */
    public static function setDefaultQueueType(string $queue) {
        static::$defaultQueueType = $queue;
    }

    /**
     * @return string
     */
    public static function getDefaultQueueType() : string {
        return static::$defaultQueueType;
    }

    /**
     * @param string $queueName
     * @param string $queueType
     * @return array
     */
    public static function getOptions(string $queueName, string $queueType = '') {
        if (empty($queueType)) {
            $queueType = static::getDefaultQueueType();
        }
        $options = static::$config[$queueType][$queueName] ?? [];
        $options['connectionName'] = $queueName;
        return $options;
    }

    /**
     * @param string $connection
     * @return int|string
     * @throws QueueException
     * @deprecated после переделки на рэбит не только потеряло актуальность, но и функциональность
     */
    public static function getQueueNameByConnection(string $connection) {
        foreach(static::$config as $queueName => $conf) {
            if ($conf['connection'] == $connection) {
                return $queueName;
            }
        }
        throw new QueueException('queue with connection {' . $connection . '} not found');
    }

    /**
     * @return mixed
     */
    public static function getRandomWorker() {
        return array_random(static::$workers);
    }
}