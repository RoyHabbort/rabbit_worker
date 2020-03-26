<?php

namespace App\V3\Services\Queue;

use App\V3\Services\LoggerService;

class BaseQueueHandler {

    public function handle(string $action, array $payload = []) {
        try {
            return call_user_func_array([$this, $action . 'Action'], $payload);
        } catch (\Exception $e) {
            LoggerService::error($e);
            return false;
        }
    }
}