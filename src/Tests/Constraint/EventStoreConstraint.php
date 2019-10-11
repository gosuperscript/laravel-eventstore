<?php

namespace DigitalRisks\LaravelEventStore\Tests\Constraint;

use DigitalRisks\LaravelEventStore\Client;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Runner\Version;

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

        if (!empty($compareEvent->data) && !is_callable($compareEvent->data)) {
            $compareData = $compareEvent->data;
            $compareEvent->data = function (array $eventData) use ($compareData) {
                return array_intersect_key($eventData, $compareData) == $compareData;
            };
        }

        if (!empty($compareEvent->metaData) && !is_callable($compareEvent->metaData)) {
            $compareData = $compareEvent->metaData;
            $compareEvent->metaData = function (array $eventData) use ($compareData) {
                return array_intersect_key($eventData, $compareData) == $compareData;
            };
        }

        foreach ($response->entries as $esEvent) {
            if ($compareEvent->eventType == $esEvent->eventType &&
                (!empty($compareEvent->streamName) ? $compareEvent->streamName == $esEvent->streamId : true) &&
                (!empty($compareEvent->data) ? ($compareEvent->data)(json_decode($esEvent->data, true)) : true) &&
                (!empty($compareEvent->metaData) ? ($compareEvent->metaData)(json_decode($esEvent->metaData, true)) : true)
            ) {
                return true;
            }
        }

        return false;
    }
}
