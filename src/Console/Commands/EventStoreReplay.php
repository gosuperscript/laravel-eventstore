<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use DigitalRisks\LaravelEventStore\Client;
use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\EventStore as LaravelEventStore;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;
use Illuminate\Support\Facades\Event;
use Rxnet\EventStore\Data\EventRecord as EventData;
use Rxnet\EventStore\Record\JsonEventRecord;

class EventStoreReplay extends Command
{
    protected $signature = 'eventstore:replay {stream} {events}';

    protected $description = 'Replay a single or range of events';
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        parent::__construct();
    }

    public function handle()
    {
        $stream = $this->argument('stream');
        $events = explode('-', $this->argument('events'));

        if (count($events) > 1) {
            $events = range($events[0], $events[1]);
        }

        $progressBar = $this->output->createProgressBar(count($events));
        $progressBar->start();

        foreach ($events as $key => $event) {
            $response = $this->client->get("/streams/$stream/$event", [
                'headers' => [
                    'Accept' => 'application/vnd.eventstore.atom+json'
                ]
            ]);

            $eventData = json_decode($response->getBody()->getContents(), true);

            try {
                $this->dispatch($eventData);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->info("Error: " . $e->getMessage());
                report($e);
            }
        }

        $progressBar->finish();
    }

    public function dispatch(array $eventData): void
    {
        $serializedEvent = $this->makeSerializableEvent($eventData);

        $type = $serializedEvent->getType();
        
        if ($localEvent = $this->mapToLocalEvent($serializedEvent)) {
            $event = $localEvent;
            $payload = null;
        } else {
            $event = $type;
            $payload = $serializedEvent;
        }

        event($event, $payload);
    }

    protected function mapToLocalEvent($event)
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

    private function makeSerializableEvent(array $event): JsonEventRecord
    {
        $data = new EventData();
        $data->setEventId($event['content']['eventId']);
        $data->setEventType($event['content']['eventType']);
        $data->setEventNumber($event['content']['eventNumber']);
        $data->setData(json_encode($event['content']['data']));
        $data->setEventStreamId($event['content']['eventStreamId']);
        $data->setMetadata(json_encode($this->safeGetMetadata($event)));
        $data->setCreatedEpoch(strtotime($event['updated']) * 1000);

        return new JsonEventRecord($data);
    }

    private function safeGetMetadata(array $event)
    {
        return $event['content']['metadata'] === '' ? [] : $event['content']['metadata'];
    }
}
