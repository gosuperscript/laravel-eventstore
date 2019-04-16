<?php

namespace Mannum\EventStore;

abstract class Stream
{
    public $name;
    protected $key;

    public function __construct()
    {
        if (!$this->key) {
            throw new \Exception('Stream key not defined');
        }

        $streamName = config('services.eventstore.streams.'.$this->key);

        if (!$streamName) {
            throw new \Exception('Stream name not defined');
        }

        $this->name = $streamName;
    }
}
