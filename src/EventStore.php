<?php

namespace DigitalRisks\LaravelEventStore;

use Illuminate\Support\Facades\Log;

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
    public static $infoLogger;

    public static $errorLogger;

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
    public static function logger(?callable $infoCallback = null, ?callable $errorCallback = null)
    {
        $infoCallback = $infoCallback ?: function($message){
            return Log::info($message);
        };

        $errorCallback = $errorCallback ?: function($message){
            return Log::error($message);
        };

        static::$infoLogger = $infoCallback;
        static::$errorLogger = $errorCallback;
    }
}
