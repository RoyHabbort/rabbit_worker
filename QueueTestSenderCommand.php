<?php

namespace App\V3\Console\Commands;

use App\Models\TestItemId;
use App\V3\Console\Jobs\Items\ItemAdd;
use App\V3\Console\Jobs\Items\ItemEdit;
use App\V3\Console\Jobs\NullJob;
use App\V3\Console\Jobs\Parsers\ParserInnerAddItems;
use App\V3\Console\Jobs\Parsers\ParserInnerEditItems;
use App\V3\Console\Kernel\Command;
use App\V3\Console\Kernel\CommandInterface;
use App\V3\Helpers\ServerEnvironment;
use App\V3\Services\Queue\QueueCommandTrait;
use App\V3\Services\Queue\QueueFacade;
use App\V3\Services\Rabbit\RabbitOptions;
use App\V3\Services\Rabbit\RabbitOptionsFacade;

class QueueTestSenderCommand extends Command implements CommandInterface {

    use QueueCommandTrait;

    public static $signature = 'queue:send';

    public function handle($arguments = []) {

        $this->display('start queue');

        $queueName = $this->option('queue');

        $itemId = rand ( 1000000 , 9999999 );

        //ItemAdd - это экземпляр работы
        QueueFacade::push($queueName, new ItemAdd(), ['item_id' => $itemId], 0, 5);
    }
}