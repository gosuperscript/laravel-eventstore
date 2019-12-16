<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\EventStore as LaravelEventStore;
use EventLoop\EventLoop;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionProperty;
use Rxnet\EventStore\Data\EventRecord as EventData;
use Rxnet\EventStore\EventStore;
use Rxnet\EventStore\Record\AcknowledgeableEventRecord;
use Rxnet\EventStore\Record\EventRecord;
use Rxnet\EventStore\Record\JsonEventRecord;
use TypeError;
use Illuminate\Support\Facades\Event;

class EventStoreWorkerThread extends Command
{
    protected $signature = 'eventstore:worker-thread
        {--stream= : Name of the stream to run on}
        {--type=persistent : Type of stream (persistent / volatile)}';

    protected $description = 'Worker handling incoming event streams from ES';

    private $loop;

    public function __construct()
    {
        parent::__construct();

        $this->loop = EventLoop::getLoop();
    }

    public function handle(): void
    {
        if (!$this->option('stream')) {
            $this->info("Stream option is required");
            return;
        }

        $this->loop->stop();
        try {
            $this->processStream();
            $this->loop->run();
        } catch (\Exception $e) {
            report($e);
        }

        $this->error('Lost connection with EventStore - reconnecting');
        sleep(1);

        $this->handle();
    }

    private function connect($callback): void
    {
        $eventStore = new EventStore();
        $eventStore->connect(config('eventstore.tcp_url'))
            ->subscribe(function () use ($callback, $eventStore) {
                $callback($eventStore);
            }, 'report');
    }

    private function processStream(): void
    {
        $this->connect(function ($eventStore) {
            if ($this->option('type') == 'volatile') {
                $this->processVolatileStream($eventStore, $this->option('stream'));
            }

            if ($this->option('type') == 'persistent') {
                $this->processPersistentStream($eventStore, $this->option('stream'));
            }
        });
    }

    private function processPersistentStream(EventStore $eventStore, string $stream): void
    {
        $eventStore->persistentSubscription($stream, config('eventstore.group'))
            ->subscribe(function (AcknowledgeableEventRecord $event) {
                try {
                    $this->dispatch($event);

                    return $event->ack();
                } catch (\Exception $e) {
                    report($e);

                    return $event->nack($event::NACK_ACTION_PARK);
                }
            });
    }

    private function processVolatileStream(EventStore $eventStore, string $stream): void
    {
        $eventStore->volatileSubscription($stream)
            ->subscribe(function (EventRecord $event) {
                try {
                    $this->dispatch($event);
                } catch (\Exception $e) {
                    $this->dumpEvent($event);
                    report($e);
                }
            }, 'report');
    }

    protected function safeGetMetadata(EventRecord $event)
    {
        try {
            return $event->getMetadata() ?? [];
        } catch (TypeError $e) {
            return [];
        }
    }

    public function dispatch(EventRecord $eventRecord): void
    {
        $serializedEvent = $payload = $this->makeSerializableEvent($eventRecord);
        $event = $serializedEvent->getType();

        if ($localEvent = $this->mapToLocalEvent($serializedEvent)) {
            $event = $localEvent;
            $payload = null;
        }

        $url = parse_url(config('eventstore.http_url'));
        $url = "{$url['scheme']}://{$url['host']}:{$url['port']}/web/index.html#";
        $type = $serializedEvent->getType();
        $stream = $serializedEvent->getStreamId();
        $number = $serializedEvent->getNumber();
        $hasListener = Event::hasListeners($type);

        $metadata = json_encode(['type' => $event, 'hasListeners' => $hasListener]);

        $this->info("{$url}/streams/{$stream}/{$number} {$metadata}");
        event($event, $payload);
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
}
