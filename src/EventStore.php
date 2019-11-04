<?php

namespace DigitalRisks\LaravelEventStore;

class EventStore
{
    /**
     * Variable for event to class.
     *
     * @var callable
     */
    public static $eventToClass;

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
}
