<?php

namespace DigitalRisks\LaravelEventStore;

use Rxnet\EventStore\EventStore;
use Rxnet\EventStore\Record\AcknowledgeableEventRecord;
use Rxnet\EventStore\Record\EventRecord;
use DigitalRisks\LaravelEventStore\AbstractWorker;
use EventLoop\EventLoop;
use Illuminate\Console\Command;
use Rxnet\EventStore\Data\EventRecord as EventData;
use Rxnet\EventStore\Record\JsonEventRecord;

class EventStoreWorker extends Command
{
    protected $signature = 'eventstore:worker
        {--persist : Run persistent mode.}
        {--volatile : Run volatile mode.}
        {--parallel= : How many events to run in parallel.}
        {--timeout= : How long the event should time out for.}';

    protected $description = 'Worker handling incoming events from ES';

    protected $loop;

    private $timeout = 10;

    public function __construct()
    {
        parent::__construct();

        $this->loop = EventLoop::getLoop();
    }

    public function handle(): void
    {
        $this->timeout = $this->option('timeout', 10);
        $this->loop->stop();

        try {
            $this->processAllStreams();
            $this->loop->run();
        } catch (\Exception $e) {
            report($e);
        }

        $this->error("Lost connection with EventStore - reconnecting in $this->timeout");

        sleep($this->timeout);

        $this->handle();
    }

    private function processAllStreams(): void
    {
        if($this->option('persist') || (!$this->option('persist') && !$this->option('volatile'))) {
            $this->connectToStream('eventstore.streams');
        }

        if($this->option('volatile')) {
            $this->connectToStream('eventstore.volatile_streams', function(EventStore $eventStore, string $stream) {
                $this->processVolatileStream($eventStore, $stream);
            });
        }
    }


    private function connectToStream($config, $callback = null): void
    {
        $streams = config($config);

        foreach ($streams as $stream) {
            $eventStore = new EventStore();
            $connection = $eventStore->connect(config('eventstore.tcp_url'));
            $connection->subscribe(function () use ($eventStore, $stream, $callback) {
                if (!$callback) {
                    $this->processStream($eventStore, $stream);
                } else {
                    $callback($eventStore, $stream);
                }
            }, 'report');
        }
    }

    private function processStream($eventStore, string $stream): void
    {
        $eventStore
            ->persistentSubscription($stream, config('eventstore.group'), $this->option('parallel') ?? 1)
            ->subscribe(function (AcknowledgeableEventRecord $event) {
                $url = config('eventstore.http_url') . "/streams/{$event->getStreamId()}/{$event->getNumber()} (persistent)";
                $this->info($url);
                try {
                    $this->dispatch($event);
                    $event->ack();
                } catch (\Exception $e) {
                    dump([
                        'id' => $event->getId(),
                        'number' => $event->getNumber(),
                        'stream' => $event->getStreamId(),
                        'type' => $event->getType(),
                        'created' => $event->getCreated(),
                        'data' => $event->getData(),
                        'metadata' => $event->getMetadata(),
                    ]);
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
                $url = config('eventstore.http_url') . "/streams/{$event->getStreamId()}/{$event->getNumber()} (volatile)";
                $this->info($url);
                try {
                    $this->dispatch($event);
                    $event->ack();
                } catch (\Exception $e) {
                    dump([
                        'id' => $event->getId(),
                        'number' => $event->getNumber(),
                        'stream' => $event->getStreamId(),
                        'type' => $event->getType(),
                        'created' => $event->getCreated(),
                        'data' => $event->getData(),
                        'metadata' => $event->getMetadata(),
                    ]);
                    $event->nack();
                    report($e);
                }
            }, 'report');
    }

    private function dispatch(EventRecord $eventRecord): void
    {
        $event = $this->makeSerializableEvent($eventRecord);

        $type = $event->getType();
        $class = config('eventstore.namespace') . '\\' . $type;

        class_exists($class) ? event(new $class($event)) : event($type, $event);
    }

    private function makeSerializableEvent(EventRecord $event): JsonEventRecord
    {
        $data = new EventData();

        $data->setEventType($event->getType());
        $data->setCreatedEpoch($event->getCreated()->getTimestamp() * 1000);
        $data->setData(json_encode($event->getData()));
        $data->setMetadata(json_encode($event->getMetadata()));

        return new JsonEventRecord($data);
    }
}
