<?php

namespace Mannum\LaravelEventStore;

class SendToEventStoreListener
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function handle(ShouldBeEventStored $event)
    {
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
