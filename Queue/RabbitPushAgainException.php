<?php

namespace App\V3\Services\Queue;

use App\V3\Services\Rabbit\RabbitException;
use App\V3\Services\Rabbit\RabbitJobMessage;
use Throwable;

class RabbitPushAgainException extends RabbitException {

    /** @var RabbitJobCommandInterface  */
    protected $executor;
    /** @var RabbitJobMessage  */
    protected $rabbitMessage;

    public function __construct(RabbitJobMessage $rabbitJobMessage, RabbitJobCommandInterface $rabbitJobCommand, $message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->executor = $rabbitJobCommand;
        $this->rabbitMessage = $rabbitJobMessage;
    }

    /**
     * @return RabbitJobCommandInterface
     */
    public function getExecutor(): RabbitJobCommandInterface {
        return $this->executor;
    }

    /**
     * @return RabbitJobMessage
     */
    public function getRabbitMessage(): RabbitJobMessage {
        return $this->rabbitMessage;
    }

}