<?php

namespace Mannum\LaravelEventStore;

use Illuminate\Console\Command;

use EventLoop\EventLoop;
use Rxnet\EventStore\EventStore;
use Rxnet\EventStore\Record\AcknowledgeableEventRecord;

class EventStoreWorker extends Command
{
    private $loop;

    private $timeout = 10;

    protected $signature = 'eventstore:worker';

    protected $description = 'Worker handling incoming events from ES';

    protected $eventstore;

    public function __construct()
    {
        parent::__construct();

        $this->loop = EventLoop::getLoop();
    }

    public function handle()
    {
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

    public function processAllStreams()
    {
        $eventStore = new EventStore();
        $connection = $eventStore->connect(config('eventstore.tcp_url'));
        $streams = config('eventstore.streams');

        $connection->subscribe(function () use ($eventStore, $streams) {
            foreach ($streams as $stream) {
                $this->processStream($eventStore, $stream);
            }
        }, 'report');
    }

    private function processStream($eventStore, string $stream)
    {
        $eventStore
            ->persistentSubscription($stream, config('eventstore.group'))
            ->subscribe(function (AcknowledgeableEventRecord $event) {
                $url = config('eventstore.http_url')."/streams/{$event->getStreamId()}/{$event->getNumber()}";
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

    protected function dispatch(AcknowledgeableEventRecord $event)
    {
        $type = $event->getType();
        $class = config('eventstore.namespace') . '\\' . $type;

        class_exists($class) ? event(new $class($event)) : event($type, $event);
    }
}
