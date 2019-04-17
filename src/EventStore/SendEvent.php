<?php

namespace Mannum\EventStore;

abstract class SendEvent implements ShouldBeSent
{
    public $name;
    public $stream;
    
    public $payload;
    public $metadata;

    public function __construct(array $payload = [], array $metadata = [])
    {
        if (!$this->name) {
            throw new \Exception('Name not defined');
        }

        $this->setMetadata($metadata);
        $this->setPayload($payload);
    }

    protected function setPayload($payload)
    {
        $this->payload = $this->serialize($payload);
    }

    protected function setMetadata($metadata)
    {
        $this->metadata = $this->serialize($metadata);
    }

    protected function serialize(array $data = [])
    {
        $return = [];

        foreach ($data as $k => $v) {
            if ($v instanceof \DateTime) {
                $return[$k] = $v->format(\DateTime::RFC3339);
            } else {
                $return[$k] = $v;
            }
        }

        return $return;
    }

    public function getStream()
    {
        return config("services.eventstore.streams.{$this->stream}");
    }
}
