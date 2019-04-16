<?php

declare(strict_types=1);

namespace Mannum\Console\Commands;

use Illuminate\Console\Command;

use React\EventLoop\LoopInterface;
use Rxnet\EventStore\EventStore;
use Rxnet\EventStore\Record\AcknowledgeableEventRecord;

class EventStoreWorker extends Command
{
    private $loop;

    private $timeout = 10;

    protected $signature = 'eventstore:worker';

    protected $description = 'Worker handling incoming events from ES';

    protected $eventstore;

    public function __construct(LoopInterface $loop)
    {
        parent::__construct();

        $this->loop = $loop;
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
        $connection = $eventStore->connect(config('services.eventstore.url'));
        $streams = config('services.eventstore.streams');

        $connection->subscribe(function () use ($eventStore, $streams) {
            foreach ($streams as $stream) {
                $this->processStream($eventStore, $stream);
            }
        }, 'report');
    }

    private function processStream($eventStore, string $stream)
    {
        $eventStore
            ->persistentSubscription($stream, config('services.eventstore.stream_group'))
            ->subscribe(function (AcknowledgeableEventRecord $event) {
                $url = config('services.eventstore.web_url')."/streams/{$event->getStreamId()}/{$event->getNumber()}";
                $this->info($url);

                try {
                    event($event->getType(), $event);
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
}
