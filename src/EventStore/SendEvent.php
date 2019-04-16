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
            throw new \Exception(sprintf('No name defined for class %s', get_class($this)));
        }

        $this->stream = $this->getStream();

        if (!$this->stream) {
            throw new \Exception(sprintf('Stream not defined for class %s', get_class($this)));
        }

        if ($request && $request->has('trackingParams')) {
            $metadata['trackingParams'] = $request->trackingParams;
        }

        $this->payload = $this->serialize($payload);
        $this->metadata = $this->serialize($metadata);
        $this->request = $request;
    }

    public function getStream()
    {
        return new $this->streamClass();
    }

    public function serialize(array $data = [])
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
