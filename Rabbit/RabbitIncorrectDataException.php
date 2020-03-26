<?php

namespace App\V3\Services\Rabbit;

class RabbitIncorrectDataException extends RabbitException {

    protected $message = 'Incorrect enter params for job';
}