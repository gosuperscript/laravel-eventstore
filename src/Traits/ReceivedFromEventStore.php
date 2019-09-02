<?php

namespace DigitalRisks\LaravelEventStore\Traits;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use ReflectionProperty;
use Rxnet\EventStore\Record\EventRecord;

trait ReceivedFromEventStore
{
    protected $event;

    public function getEventRecord(): ?EventRecord
    {
        return $this->event;
    }

    public function setEventRecord(EventRecord $event): void
    {
        $this->event = $event;
    }
}
