<?php

namespace DigitalRisks\LaravelEventStore\Contracts;

use Ramsey\Uuid\UuidInterface;
use Rxnet\EventStore\Record\EventRecord;

interface CouldBeReceived
{
    public function getEventRecord(): ?EventRecord;

    public function setEventRecord(EventRecord $event): void;
}
