<?php

namespace DigitalRisks\LaravelEventStore\Exceptions;

use Exception;

class ConnectionLostException extends Exception
{
    public function __construct($message = 'Lost connection with EventStore - reconnecting', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
