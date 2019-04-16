<?php

namespace Mannum\EventStore;

abstract class Stream
{
    public $name;
    protected $key;

    public function __construct()
    {
        if (!$this->key) {
            throw new \Exception(sprintf('No key defined for class %s', get_class($this)));
        }

        $streamName = config('streams.'.$this->key);

        if (!$streamName) {
            throw new \Exception(sprintf('Stream not defined for class %s', get_class($this)));
        }

        $this->name = $streamName;
    }
}
