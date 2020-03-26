<?php

namespace App\V3\Services\Queue;

interface ZmqJobCommandInterface {

    public static function getSignature() : string;

    public static function getAllowedQueues() : array;

}