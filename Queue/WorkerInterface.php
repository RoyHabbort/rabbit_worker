<?php

namespace App\V3\Services\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;

interface WorkerInterface {

    public function daemon(QueueContract $queue);

}