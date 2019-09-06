<?php

namespace DigitalRisks\LaravelEventStore\Listeners;

use DigitalRisks\LaravelEventStore\Client;
use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;

class SendToEventStoreListener
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function handle(ShouldBeStored $event)
    {
        // The event has already been stored, so never send it out...
        if ($event instanceof CouldBeReceived && $event->getEventRecord()) {
            return;
        }

        $this->client->post("/streams/{$event->getEventStream()}", [
            'body' => json_encode([[
                'eventId' => $event->getEventId(),
                'eventType' => $event->getEventType(),
                'data' => $event->getData(),
                'metadata' => $event->getMetadata(),
            ]]),
            'headers' => [
                'Content-Type' => 'application/vnd.eventstore.events+json',
            ],
        ]);
    }
}
