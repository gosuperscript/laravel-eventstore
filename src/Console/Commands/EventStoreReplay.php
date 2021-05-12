<?php

namespace DigitalRisks\LaravelEventStore\Console\Commands;

use DigitalRisks\LaravelEventStore\Client;
use DigitalRisks\LaravelEventStore\Services\EventStoreEventService;
use DigitalRisks\LaravelEventStore\TestUtils\Traits\MakesEventRecords;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Rxnet\EventStore\Record\EventRecord;

class EventStoreReplay extends Command
{
    use MakesEventRecords;

    protected $signature = 'eventstore:replay {stream} {events}';

    protected $description = 'Replay a single or range of events';

    private Client $client;

    private EventStoreEventService $eventService;


    public function __construct(Client $client, EventStoreEventService $eventService)
    {
        $this->client = $client;
        $this->eventService = $eventService;

        parent::__construct();
    }

    public function handle()
    {
        $stream = $this->argument('stream');
        $events = explode('-', $this->argument('events'));
        if (!filter_var($events[0], FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException('Events only accepts integer values');
        }

        if (count($events) > 1) {
            if (!filter_var($events[1], FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException('Events only accepts integer values');
            }
            if ($events[1] <= $events[0]) {
                throw new InvalidArgumentException('Events must be an upward range');
            }
            $events = range($events[0], $events[1]);
        }

        $progressBar = $this->output->createProgressBar(count($events));
        $progressBar->start();

        foreach ($events as $event) {
            $response = $this->client->get("/streams/$stream/$event", [
                'headers' => [
                    'Accept' => 'application/vnd.eventstore.atom+json'
                ]
            ]);
            $eventData = json_decode($response->getBody()->getContents(), true)['content'];
            $eventRecord = $this->makeEventRecord(
                $eventData['eventType'],
                $eventData['data'],
                $eventData['metadata'],
                null,
                $eventData['eventStreamId']
            );

            try {
                $this->dispatch($eventRecord);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->info("Error: " . $e->getMessage());
                report($e);
            }
        }

        $progressBar->finish();
    }

    public function dispatch(EventRecord $eventRecord): void
    {
        $preparedEvent = $this->eventService->prepareEvent($eventRecord);
        event($preparedEvent['event'], $preparedEvent['payload']);
    }
}
