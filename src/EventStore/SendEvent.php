<?php

namespace Mannum\EventStore;

abstract class SendEvent implements ShouldBeSent
{
    public $name;
    public $payload;
    public $stream;
    public $metadata;

    protected $streamClass;

    public function __construct(array $payload = [], array $metadata = [], $request = null)
    {
        if (!$this->name) {
            throw new \Exception('Name not defined');
        }

        $this->setStream();
        $this->setMetadata($metadata);
        $this->setPayload($payload);
        
        $this->request = $request;
    }

    protected function setMetadata($metadata)
    {
        $this->metadata = $this->serialize($metadata);
    }

    protected function setPayload($payload)
    {
        $this->payload = $this->serialize($payload);
    }

    protected function setStream()
    {
        if (!$this->streamClass || !class_exists($this->streamClass)) {
            throw new \Exception('Stream class not defined');
        }

        $this->stream = $this->streamClass();
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
}
