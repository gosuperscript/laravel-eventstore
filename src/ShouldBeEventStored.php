<?php

namespace Mannum\LaravelEventStore;

use Ramsey\Uuid\UuidInterface;

interface ShouldBeEventStored
{
    public function getEventStream(): string;

    public function getEventType(): string;

    public function getEventId(): UuidInterface;

    public function getData(): array;

    public function getMetadata(): array;
}
