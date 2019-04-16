<?php

namespace Mannum\EventStore;

abstract class ReceiveEvent implements ShouldBeReceived
{
    public $payload;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }
}
