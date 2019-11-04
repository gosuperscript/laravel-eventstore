<?php

namespace DigitalRisks\LaravelEventStore;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class EventStore
{
    /**
     * Variable for event to class.
     *
     * @var callable
     */
    public static $eventToClass;

    /**
     * Variable for logger.
     *
     * @var callable
     */
    public static $logger;

    /**
     * Set the event class based on current event key.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function eventToClass(?callable $callback = null)
    {
        $callback = $callback ?: function ($event) {
            return 'App\Events\\' . $event->getType();
        };

        static::$eventToClass = $callback;
    }

    /**
     * Set the logger environment.
     *
     * @param callable $callback
     * @return void
     */
    public static function logger(?callable $callback = null)
    {
        $callback = $callback ?: function ($event, $type) {
            $url = parse_url(config('eventstore.http_url'));
            $url = "{$url['scheme']}://{$url['host']}:{$url['port']}/web/index.html#";
            $type = $event->getType();
            $stream = $event->getStreamId();
            $number = $event->getNumber();
            $hasListener = Event::hasListeners($type);

            Log::info("{$url}/streams/{$stream}/{$number}", ['type' => $type, 'hasListeners' => $hasListener]);
        };

        static::$logger = $callback;
    }
}
