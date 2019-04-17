<?php

namespace Mannum\EventStore;

use Ramsey\Uuid\Uuid;

class SendEventListener
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function handle($event)
    {
        $response = $this->client->post("/streams/{$event->getStream()}", [
            'body' => json_encode([[
                'eventId' => Uuid::uuid4(),
                'eventType' => $event->name,
                'data' => $event->payload,
                'metadata' => array_merge([
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                    'heroku' => collect($_ENV)->filter(function ($value, $key) {
                        return strpos($key, 'HEROKU_') === 0;
                    })->toArray(),
                ], $event->metadata),
            ]]),
            'headers' => [
                'Content-Type' => 'application/vnd.eventstore.events+json',
            ],
        ]);
    }
}
