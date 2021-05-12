<?php

namespace DigitalRisks\LaravelEventStore\Services;

use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\EventStore as LaravelEventStore;
use ReflectionClass;
use ReflectionProperty;
use Rxnet\EventStore\Data\EventRecord as EventData;
use Rxnet\EventStore\Record\EventRecord;
use Rxnet\EventStore\Record\JsonEventRecord;
use TypeError;

class EventStoreEventService
{
    public function prepareEvent(EventRecord $eventRecord): array
    {
        $serializedEvent = $this->makeSerializableEvent($eventRecord);

        $type = $serializedEvent->getType();
        $stream = $serializedEvent->getStreamId();
        $number = $serializedEvent->getNumber();

        if ($localEvent = $this->mapToLocalEvent($serializedEvent)) {
            $event = $localEvent;
            $payload = null;
        } else {
            $event = $type;
            $payload = $serializedEvent;
        }

        return [
            'event' => $event,
            'payload' => $payload,
            'type' => $type,
            'stream' => $stream,
            'number' => $number
        ];
    }

    private function makeSerializableEvent(EventRecord $event): JsonEventRecord
    {
        $data = new EventData();

        $data->setEventId($event->getId());
        $data->setEventType($event->getType());
        $data->setEventNumber($event->getNumber());
        $data->setData(json_encode($event->getData()));
        $data->setEventStreamId($event->getStreamId());
        $data->setMetadata(json_encode($this->safeGetMetadata($event)));
        $data->setCreatedEpoch($event->getCreated()->getTimestamp() * 1000);

        return new JsonEventRecord($data);
    }

    private function mapToLocalEvent($event)
    {
        $eventToClass = LaravelEventStore::$eventToClass;
        $className = $eventToClass ? $eventToClass($event) : 'App\Events\\' . $event->getType();

        if (!class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->implementsInterface(CouldBeReceived::class)) {
            return;
        }

        $localEvent = new $className();
        $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $data = $event->getData();

        foreach ($props as $prop) {
            $key = $prop->getName();
            $localEvent->$key = $data[$key] ?? null;
        }

        $localEvent->setEventRecord($event);

        return $localEvent;
    }

    private function safeGetMetadata(EventRecord $event)
    {
        try {
            return $event->getMetadata() ?? [];
        } catch (TypeError $e) {
            return [];
        }
    }
}
