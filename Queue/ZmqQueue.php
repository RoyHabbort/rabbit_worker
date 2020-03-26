<?php

namespace App\V3\Services\Queue;

use Illuminate\Queue\Queue;
use App\V3\Services\Queue\Jobs\ZmqJob;
use App\V3\Services\Zmq\ZmqInterface;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class ZmqQueue extends Queue implements QueueContract{

    /** @var ZmqInterface */
    protected $zmq;

    public function __construct(ZmqInterface $zmq) {
        $this->zmq = $zmq;
    }

    public function size($queue = null) {
        return null;
    }

    /**
     * @param object|string $job
     * @param string $data
     * @param null $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null) {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * @param string $payload
     * @param null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = []) {
        return $this->zmq->getSocket()->send($payload);
    }

    public function later($delay, $job, $data = '', $queue = null) {
        return null;
    }

    /**
     * @param null $queue
     * @return string
     */
    public function pop($queue = null) {
        return $this->zmq->getSocket()->recv();
    }

    public function getConnectionName() {
        return $this->zmq->getConnection();
    }

    /**
     * @param string $job товарищи, может я чего не знаю, но какого хрена строка проверяется на is_object у родителя?
     * @param string $data
     * @return string
     */
    protected function createPayload($job, $data = '') {
        return json_encode($job->payload());
    }
}