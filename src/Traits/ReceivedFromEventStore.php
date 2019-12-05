<?php

namespace DigitalRisks\LaravelEventStore\Traits;

use Rxnet\EventStore\Record\EventRecord;
use Rxnet\EventStore\Data\EventRecord as EventRecordData;

trait ReceivedFromEventStore
{
    protected $event;

    public function getEventRecord(): ?EventRecord
    {
        if (config('eventstore.connection') == 'sync') {
            $properties = get_object_vars($this);
            unset($properties['event']);

            return new EventRecord(new EventRecordData([
                'data' => json_encode($properties)
            ]));
        }

        return $this->event;
    }

    public function setEventRecord(EventRecord $event): void
    {
        $this->event = $event;
    }
}
