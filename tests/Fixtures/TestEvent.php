<?php

namespace DigitalRisks\LaravelEventStore\Tests\Fixtures;

use Rxnet\EventStore\Record\EventRecord;

class TestEvent
{
    public $event;

    public function __construct(EventRecord $event)
    {
        $this->event = $event;
    }
}
