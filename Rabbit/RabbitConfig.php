<?php

namespace App\V3\Services\Rabbit;

class RabbitConfig {

    /** @var  string */
    protected $host;
    /** @var  string */
    protected $port;
    /** @var  string */
    protected $user;
    /** @var  string */
    protected $password;

    public function __construct(array $configs) {
        if (empty($configs['host'])
            || empty($configs['port'])
            || empty($configs['user'])
            || empty($configs['password'])) {
            throw new RabbitOptionsExceptions('Failure read config');
        }
        $this->host = $configs['host'];
        $this->port = $configs['port'];
        $this->user = $configs['user'];
        $this->password = $configs['password'];
    }

    /**
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }
    /**
     * @return string
     */
    public function getPort(): string {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser(): string {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword(): string {
        return $this->password;
    }
}