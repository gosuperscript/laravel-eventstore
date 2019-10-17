<?php

namespace DigitalRisks\LaravelEventStore;

use DigitalRisks\LaravelEventStore\Contracts\Logger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class BasicLogger implements Logger
{
    public function eventStart($eventOrType, $payloadOrEvent)
    {
        $url = parse_url(config('eventstore.http_url'));
        $url = "{$url['scheme']}://{$url['host']}:{$url['port']}";

        $type = $eventOrType;
        $event = $payloadOrEvent;

        if (is_object($eventOrType)) {
            $type = get_class($eventOrType);
            $event = $eventOrType->getEventRecord();
        }

        $stream = $event->getStreamId();
        $number = $event->getNumber();
        $hasListeners = Event::hasListeners($type);

        Log::info("{$url}/streams/{$stream}/{$number}", ['type' => $type, 'hasListeners' => $hasListeners]);
    }
}
