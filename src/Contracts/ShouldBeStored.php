<?php

namespace DigitalRisks\LaravelEventStore\Contracts;

use Ramsey\Uuid\UuidInterface;

interface ShouldBeStored
{
    public function getEventStream(): string;

    public function getEventType(): string;

    public function getEventId(): UuidInterface;

    public function getData(): array;

    public function getMetadata(): array;
}
