<?php

namespace App\V3\Services\Rabbit;

use App\V3\Console\Jobs\Core\JobRegister;
use App\V3\Services\Queue\Jobs\RabbitJob;
use App\V3\Services\Queue\QueueFacade;
use App\V3\Services\Queue\RabbitJobCommandInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitJobMessage {

    const DEFAULT_TIMEOUT = 180;

    /** @var  string */
    protected $jobName;
    /** @var  array */
    protected $params;
    /** @var int */
    protected $attempts;
    /** @var  int */
    protected $timeoutTo;
    /** @var  int */
    protected $timeStart;
    /** @var  int */
    protected $maxTries;
    /** @var  string */
    protected $queueName;
    /** @var RabbitJobCommandInterface */
    protected $executor = null;
    /** @var AMQPMessage  */
    protected $message;

    public function __construct(AMQPMessage $message) {
        $data = json_decode($message->body, true);

        if (empty($data) || empty($data['job']) || empty($data['queue'])) {
            throw new RabbitIncorrectDataException();
        }

        $this->message = $message;

        $this->jobName = $data['job'];
        $this->params = $data['params'] ?? [];
        $this->attempts = $data['attempts'] ?? 0;
        $this->timeoutTo = $data['timeoutTo'] ?? 0;
        $this->timeStart = $data['timeStart'] ?? 0;
        $this->maxTries = $data['maxTries'] ?? RabbitJob::DEFAULT_MAX_TRIES;
        $this->queueName = $data['queue'];
    }

    /**
     * @return string
     */
    public function getJobClassName() : string {
        return JobRegister::get($this->jobName);
    }

    /**
     * @return RabbitJobCommandInterface
     */
    public function getExecutor() : RabbitJobCommandInterface {
        if (empty($this->executor)) {
            $executorClassName = $this->getJobClassName();
            $this->executor = new $executorClassName();
        }
        return $this->executor;
    }

    /**
     * @return string
     */
    public function getJobName(): string {
        return $this->jobName;
    }

    /**
     * @return array
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * @return int
     */
    public function getAttempts(): int {
        return $this->attempts;
    }

    /**
     * @return int
     */
    public function getTimeoutTo(): int {
        return $this->timeoutTo;
    }

    /**
     * @return int
     */
    public function getTimeStart(): int {
        return $this->timeStart;
    }

    /**
     * @return int
     */
    public function getMaxTries(): int {
        return $this->maxTries;
    }

    /**
     * @return string
     */
    public function getQueueName(): string {
        return $this->queueName;
    }

    /**
     * @return bool
     */
    public function isLimitAttempts() : bool{
        return $this->getAttempts() >= $this->getMaxTries();
    }

    /**
     * @return RabbitJobMessage
     */
    public function increaseAttempts() : RabbitJobMessage {
        $this->attempts++;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannel() {
        return $this->message->delivery_info['channel'];
    }

    /**
     * @return mixed
     */
    public function getDeliveryTag() {
        return $this->message->delivery_info['delivery_tag'];
    }

    /**
     * @return $this
     */
    public function confirmDelivery() {
        $channel = $this->getChannel();
        $channel->basic_ack($this->getDeliveryTag());
        return $this;
    }

    /**
     * @return bool
     */
    public function isAdventureTime() : bool {
        return $this->timeoutTo <= time();
    }

    /**
     * @return int
     */
    public function getNewTimeoutTo() : int {
        return $this->getTimeoutTo() - $this->getTimeStart();
    }

    /**
     * @param RabbitJobCommandInterface $executor
     */
    public function pushAgain(RabbitJobCommandInterface $executor) {
        QueueFacade::push(
            $this->getQueueName(),
            $executor,
            $this->getParams(),
            $this->getAttempts(),
            $this->getMaxTries(),
            $this->getNewTimeoutTo(),
            $this->getTimeStart()
        );
    }

    /**
     * @param RabbitJobCommandInterface $executor
     */
    public function pushNewAttempts(RabbitJobCommandInterface $executor) {
        //увеличиваем кол-во попыток
        $this->increaseAttempts();
        QueueFacade::push(
            $this->getQueueName(),
            $executor,
            $this->getParams(),
            $this->getAttempts(),
            $this->getMaxTries(),
            RabbitJobMessage::DEFAULT_TIMEOUT
        );
    }
}