<?php

namespace DigitalRisks\LaravelEventStore\Contracts;

use Ramsey\Uuid\UuidInterface;
use Rxnet\EventStore\Record\EventRecord;

interface Logger
{
    public function eventStart($event, $payload);
}
