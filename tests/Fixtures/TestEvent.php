<?php

namespace DigitalRisks\LaravelEventStore\Tests\Fixtures;

use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\Traits\ReceivedFromEventStore;
use Rxnet\EventStore\Record\EventRecord;

class TestEvent implements CouldBeReceived
{
    use ReceivedFromEventStore;

    public $hello;

    public function __construct(string $value)
    {
        $this->hello = $value;
    }
}
