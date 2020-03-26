<?php

namespace App\V3\Services\Queue\Jobs;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Jobs\Job;
use App\V3\Services\Queue\QueueOptions;

/**
 * Class ZmqJob
 * @package App\V3\Services\Queue\Jobs
 * @deprecated нужно было только для zmq
 */
class ZmqJob extends Job implements JobContract {

    const DEFAULT_MAX_TRIES = 3;

    /**
     * The class name of the job.
     *
     * @var string
     */
    protected $job;

    /**
     * The queue message data.
     *
     * @var string
     */
    protected $payload;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var int
     */
    protected $maxTries = self::DEFAULT_MAX_TRIES;

    /**
     * @var int
     */
    protected $attempts = 0;

    /** @var  int */
    protected $timeoutTo;
    /** @var int  */
    protected $timeStart;

    /**
     * ZmqJob constructor.
     * @param string $executorSignature
     * @param Queue $queue
     * @param array $params
     * @param int $attempts
     * @param int $maxTries
     * @param int $timeoutTo
     * @param int $timeStart
     */
    public function __construct(
        string $executorSignature,
        Queue $queue,
        array $params = [],
        int $attempts = 0,
        int $maxTries = self::DEFAULT_MAX_TRIES,
        int $timeoutTo = 0,
        int $timeStart = 0
    ) {
        $this->params = $params;

        $this->job = $executorSignature;
        $this->queue = $queue;
        $this->connectionName = $queue->getConnectionName();
        $this->maxTries = $maxTries;
        $this->attempts = $attempts;

        $this->timeStart = !empty($timeStart) ? $timeStart : time();
        $this->timeoutTo = !empty($timeoutTo) ? $this->timeStart + $timeoutTo : 0;
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
    public function getMaxTries(): int {
        return $this->maxTries;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return '';
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue()
    {
        return 'sync';
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload()
    {
        return [
            'maxTries' => $this->getMaxTries(),
            'timeout' => 0,
            'timeoutAt' => 0,
            'job' => $this->job,
            'params' => $this->getParams(),
            'attempts' => $this->attempts,
            'queue' => QueueOptions::getQueueNameByConnection($this->connectionName),
            'timeoutTo' => $this->timeoutTo,
            'timeStart' => $this->timeStart
        ];
    }

}