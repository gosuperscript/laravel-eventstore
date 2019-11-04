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
     * Handle when an event has started.
     *
     * @var callable
     */
    public static $onStart;

    /**
     * Handle when an event has been succesfull.
     *
     * @var callable
     */
    public static $onSuccess;

    /**
     * Handle when an event has errored.
     *
     * @var callable
     */
    public static $onError;

    /**
     * Handle when an event has finished.
     *
     * @var callable
     */
    public static $onFinish;


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

    /**
     * Set what happends when an event starts.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function onStart(?callable $callback = null)
    {
        $callback = $callback ?: function ($event) {
            return;
        };

        static::$onStart = $callback;
    }

    /**
     * Set what happends when an event is successful.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function onSuccess(?callable $callback = null)
    {
        $callback = $callback ?: function ($event) {
            return;
        };

        static::$onSuccess = $callback;
    }

    /**
     * Set what happends when an event errors.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function onError(?callable $callback = null)
    {
        $callback = $callback ?: function ($e, $event) {
            return;
        };

        static::$onError = $callback;
    }

    /**
     * Set what happends when an event finishes.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function onFinish(?callable $callback = null)
    {
        $callback = $callback ?: function ($event) {
            return;
        };

        static::$onFinish = $callback;
    }
}
