<?php

namespace Mannum\LaravelEventStore\Tests\Constraint;

use Mannum\LaravelEventStore\Client;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\Constraint\Constraint;

abstract class EventStoreConstraint extends Constraint
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        if (version_compare(Version::id(), '8.0.0', '<')) {
            parent::__construct();
        }
    }

    protected function checkStream($compareEvent)
    {
        try {
            $response = $this->client->get("/streams/{$compareEvent->streamName}/head/backward/{$compareEvent->limit}?embed=body");
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                return false;
            }
        }

        $response = json_decode($response->getBody()->getContents());

        foreach ($response->entries as $esEvent) {
            if ($compareEvent->eventType == $esEvent->eventType &&
                (!empty($compareEvent->streamName) ? $compareEvent->streamName == $esEvent->streamId : true) &&
                (!empty($compareEvent->data) ? array_intersect_key(json_decode($esEvent->data, true), $compareEvent->data) == $compareEvent->data : true) &&
                (!empty($compareEvent->metaData) ? array_intersect_key(json_decode($esEvent->metaData, true), $compareEvent->metaData) == $compareEvent->metaData : true)
            ) {
                return true;
            }
        }

        return false;
    }
}
