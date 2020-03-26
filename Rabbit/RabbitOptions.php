<?php

namespace App\V3\Services\Rabbit;

use App\Config;

class RabbitOptions {

    protected $options = [];

    /**
     * @param string $connectionName
     * @return RabbitConfig
     */
    public function getOption(string $connectionName) : RabbitConfig {
        if(empty($this->options[$connectionName])) {
            //берём массив из ини файла. И делаем сетОпции
            $configArray = Config::instance()->get($connectionName);
            $this->setOption($connectionName, $configArray);
        }
        return $this->options[$connectionName];
    }

    /**
     * @param string $connectionName
     * @param array $config
     * @return RabbitOptions
     */
    public function setOption(string $connectionName, array $config) : RabbitOptions {
        $this->options[$connectionName] = new RabbitConfig($config);
        return $this;
    }
}