<?php

namespace App\V3\Services\Queue;

interface RabbitJobCommandInterface {

    public static function getSignature() : string;

}