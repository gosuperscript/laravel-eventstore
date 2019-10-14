<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use Illuminate\Console\Command;

use Carbon\Carbon;
use EventLoop\EventLoop;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionProperty;
use Rxnet\EventStore\EventStore;
use Rxnet\EventStore\Data\EventRecord as EventData;
use Rxnet\EventStore\Record\AcknowledgeableEventRecord;
use Rxnet\EventStore\Record\EventRecord;
use Rxnet\EventStore\Record\JsonEventRecord;
use TypeError;

class EventStoreWorker extends Command
{
    protected $signature = 'eventstore:worker {--parallel= : How many events to run in parallel.} {--timeout= : How long the event should time out for.}';

    protected $description = 'Worker handling incoming events from ES';

    private $loop;

    public function __construct()
    {
        parent::__construct();

        $this->loop = EventLoop::getLoop();
    }

    public function handle(): void
    {
        $timeout = $this->option('timeout') ?? 10;

        $this->loop->stop();

        try {
            $this->processAllStreams();
            $this->loop->run();
        } catch (\Exception $e) {
            report($e);
        }

        $this->error('Lost connection with EventStore - reconnecting in ' . $timeout);

        sleep($timeout);

        $this->handle();
    }

    private function processAllStreams(): void
    {
        $this->connectToStream(config('eventstore.subscription_streams'), function (EventStore $eventStore, string $stream) {
            $this->processPersistentStream($eventStore, $stream);
        });

        $this->connectToStream(config('eventstore.volatile_streams'), function (EventStore $eventStore, string $stream) {
            $this->processVolatileStream($eventStore, $stream);
        });
    }


    private function connectToStream($streams, $callback): void
    {
        foreach ($streams as $stream) {
            $eventStore = new EventStore();
            $connection = $eventStore->connect(config('eventstore.tcp_url'));
            $connection->subscribe(function () use ($eventStore, $stream, $callback) {
                $callback($eventStore, $stream);
            }, 'report');
        }
    }

    private function processPersistentStream($eventStore, string $stream): void
    {
        $eventStore
            ->persistentSubscription($stream, config('eventstore.group'), $this->option('parallel') ?? 1)
            ->subscribe(function (AcknowledgeableEventRecord $event) {
                $this->logEventStart($event, 'persistent');
                try {
                    $this->dispatch($event);
                    $event->ack();
                } catch (\Exception $e) {
                    $this->dumpEvent($event);
                    $event->nack();
                    report($e);
                }
            }, 'report');
    }

    private function processVolatileStream($eventStore, string $stream): void
    {
        $eventStore
            ->volatileSubscription($stream)
            ->subscribe(function (EventRecord $event) {
                $this->logEventStart($event, 'volatile');
                try {
                    $this->dispatch($event);
                } catch (\Exception $e) {
                    $this->dumpEvent($event);
                    report($e);
                }
            }, 'report');
    }

    protected function logEventStart(EventRecord $event, $type = '')
    {
        $url = parse_url(config('eventstore.http_url'));
        $url = "{$url['scheme']}://{$url['host']}:{$url['port']}";

        $stream = $event->getStreamId();
        $number = $event->getNumber();

        Log::info("{$url}/streams/{$stream}/{$number}", ['type' => $type]);
    }

    protected function dumpEvent(EventRecord $event)
    {
        dump([
            'id' => $event->getId(),
            'number' => $event->getNumber(),
            'stream' => $event->getStreamId(),
            'type' => $event->getType(),
            'created' => $event->getCreated(),
            'data' => $event->getData(),
            'metadata' => $this->safeGetMetadata($event),
        ]);
    }

    protected function safeGetMetadata(EventRecord $event)
    {
        try {
            return $event->getMetadata();
        }
        catch (TypeError $e) {
            return [];
        }
    }

    public function dispatch(EventRecord $eventRecord): void
    {
        $event = $this->makeSerializableEvent($eventRecord);

        if ($localEvent = $this->mapToLocalEvent($event)) {
            event($localEvent);
        }
        else {
            event($event->getType(), $event);
        }
    }

    private function makeSerializableEvent(EventRecord $event): JsonEventRecord
    {
        $data = new EventData();

        $data->setEventType($event->getType());
        $data->setCreatedEpoch($event->getCreated()->getTimestamp() * 1000);
        $data->setData(json_encode($event->getData()));
        $data->setMetadata(json_encode($this->safeGetMetadata($event)));

        return new JsonEventRecord($data);
    }

    protected function mapToLocalEvent($event)
    {
        $eventToClass = config('eventstore.event_to_class');
        $className = $eventToClass ? $eventToClass($event) : 'App\Events\\' . $event->getType();

        if (! class_exists($className)) return;

        $reflection = new ReflectionClass($className);

        if (! $reflection->implementsInterface(CouldBeReceived::class)) return;

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