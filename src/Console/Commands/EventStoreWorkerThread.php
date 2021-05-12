<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use DigitalRisks\LaravelEventStore\EventStore as LaravelEventStore;
use DigitalRisks\LaravelEventStore\Services\EventStoreEventService;
use EventLoop\EventLoop;
use Illuminate\Console\Command;
use Rxnet\EventStore\EventStore;
use Rxnet\EventStore\Record\AcknowledgeableEventRecord;
use Rxnet\EventStore\Record\EventRecord;
use Illuminate\Support\Facades\Event;

class EventStoreWorkerThread extends Command
{
    protected $signature = 'eventstore:worker-thread
        {--stream= : Name of the stream to run on}
        {--type=persistent : Type of stream (persistent / volatile)}';

    protected $description = 'Worker handling incoming event streams from ES';

    private EventStoreEventService $eventService;

    private $loop;

    public function __construct(EventStoreEventService $eventService)
    {
        parent::__construct();

        $this->eventService = $eventService;
        $this->loop = EventLoop::getLoop();
    }

    public function handle(): void
    {
        if (!$this->option('stream')) {
            report(new \Exception('Stream option is required'));
            return;
        }

        $this->loop->stop();
        try {
            $this->processStream();
            $this->loop->run();
        } catch (\Exception $e) {
            report($e);
        }

        report(new \Exception('Lost connection with EventStore - reconnecting'));
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
            }, 'report');
    }

    private function processVolatileStream(EventStore $eventStore, string $stream): void
    {
        $eventStore->volatileSubscription($stream)
            ->subscribe(function (EventRecord $event) {
                try {
                    $this->dispatch($event);
                } catch (\Exception $e) {
                    report($e);
                }
            }, 'report');
    }

    public function dispatch(EventRecord $eventRecord): void
    {
        $preparedEvent = $this->eventService->prepareEvent($eventRecord);

        $url = parse_url(config('eventstore.http_url'));
        $url = "{$url['scheme']}://{$url['host']}:{$url['port']}/web/index.html#";

        $hasListener = Event::hasListeners($preparedEvent['type']);
        $metadata = ['type' => $preparedEvent['type'], 'hasListeners' => $hasListener];

        (LaravelEventStore::$threadLogger)("{$url}/streams/{$preparedEvent['stream']}/{$preparedEvent['number']}", $metadata);
        event($preparedEvent['event'], $preparedEvent['payload']);
    }
}
