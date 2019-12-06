<?php

namespace DigitalRisks\LaravelEventStore\Traits;

use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use Rxnet\EventStore\Record\EventRecord;
use Rxnet\EventStore\Data\EventRecord as EventRecordData;
use Rxnet\EventStore\Record\JsonEventRecord;

trait ReceivedFromEventStore
{
    protected $event;

    public function getEventRecord(): ?EventRecord
    {
        if (config('eventstore.connection') == 'sync') {
            return $this->createEventRecord();
        }

        return $this->event;
    }

    public function setEventRecord(EventRecord $event): void
    {
        $this->event = $event;
    }

    protected function createEventRecord()
    {
        if ($this instanceof ShouldBeStored) {
            $data = $this->getData();
            $metadata = $this->getMetadata();
        }
        else {
            $data = get_object_vars($this);
            $metadata = [];
            unset($data['event']);
        }

        return new JsonEventRecord(new EventRecordData([
            'data' => json_encode($data),
            'metadata' => json_encode($metadata),
        ]));
    }
}
