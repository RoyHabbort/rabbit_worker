<?php

namespace App\V3\Console\Commands;

use App\CacheFactory;
use App\V3\Console\Jobs\NullJob;
use App\V3\Console\Kernel\Command;
use App\V3\Console\Kernel\CommandInterface;
use App\V3\Services\Queue\QueueCommandTrait;
use App\V3\Services\Queue\QueueFacade;
use App\V3\Services\Queue\QueueOptions;

/**
 * Class StopQueueProcess
 * @package App\V3\Console\Commands
 * @deprecated нужен был только для zmq
 */
class StopQueueProcess extends Command implements CommandInterface {

    use QueueCommandTrait;

    public static $signature = 'queue:stop';

    public function handle() {

        $cache = CacheFactory::getInstance()->get('memcached');

        //@todo: реализовать стоп очереди по имени
        $this->display('stop all queue');

        $cache->set('queue_restart_signal', time(), 3*60);

        foreach(QueueOptions::$workers as $worker) {
            QueueFacade::push($worker, new NullJob());
        }
        QueueFacade::push(QueueOptions::GLOBAL_QUEUE, new NullJob());
    }
}