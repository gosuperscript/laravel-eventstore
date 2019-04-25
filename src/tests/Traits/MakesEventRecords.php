<?php

namespace Mannum\LaravelEventStore\Tests\Traits;

use Illuminate\Support\Carbon;
use Rxnet\EventStore\Data\EventRecord;
use Rxnet\EventStore\Record\JsonEventRecord;

trait MakesEventRecords
{
    public function makeEventRecord($type, $data, $metadata = [], $created = null)
    {
        $event = new EventRecord();
        $created = new Carbon($created);

        $event->setEventType($type);
        $event->setCreatedEpoch($created->getTimestamp() * 1000);
        $event->setData(json_encode($data));
        $event->setMetadata(json_encode($metadata));

        return new JsonEventRecord($event);
    }
}
