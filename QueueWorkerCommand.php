<?php

namespace App\V3\Console\Commands;

use App\V3\Console\Kernel\Command;
use App\V3\Console\Kernel\CommandInterface;
use App\V3\Services\Queue\QueueCommandTrait;
use App\V3\Services\Queue\QueueFacade;
use App\V3\Services\Queue\QueueRestartSignal;
use App\V3\Services\Queue\WorkerRabbit;

class QueueWorkerCommand extends Command implements CommandInterface {

    use QueueCommandTrait;

    public static $signature = 'queue:work';

    public function handle() {
        $this->display('start queue');

        $queue = QueueFacade::getQueue($this->option('queue'));

        try {
            (new WorkerRabbit())->daemon($queue);
        }
        catch (QueueRestartSignal $signal) {
            $this->logNotice('Close by signal');
        }
        catch (\Throwable $throwable) {
            $this->logError($throwable->getMessage());
        }
    }

}