<?php

namespace App\V3\Services\Rabbit;

use App\V3\Helpers\ServerEnvironment;

class RabbitOptionsFacade {

    protected static $instance;

    /** @var RabbitOptions */
    protected $setting;

    public static function getInstance() : RabbitOptionsFacade {
        if (null === static::$instance) {
            $instance = new static();
            $instance->setting = new RabbitOptions();
            static::$instance = $instance;
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
     * @param string $connectionName
     * @return RabbitConfig
     */
    public static function getOptions(string $connectionName) : RabbitConfig{
        $instance = static::getInstance();
        return $instance->setting->getOption($connectionName);
    }

    /**
     * @return string
     */
    public static function getConnectionName() : string {
        //@todo: убрать на config
        if (ServerEnvironment::isPro()) {
            $connectionName = static::RABBIT_PRO;
        }
        elseif (ServerEnvironment::isDev()) {
            $connectionName = static::RABBIT_DEV;
        }
        elseif(ServerEnvironment::isLocal()) {
            $connectionName = static::RABBIT_LOCAL;
        }
        return $connectionName;
    }

    /**
     * @return RabbitConfig
     */
    public static function getEnvironmentOptions() {
        $connectionName = static::getConnectionName();
        return static::getOptions($connectionName);
    }
}