<?php

namespace DigitalRisks\LaravelEventStore;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

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
    public static $workerLogger;

    /**
     * Variable for logger.
     *
     * @var callable
     */
    public static $threadLogger;

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
    public static function workerLogger(?callable $logger = null)
    {
        static::$workerLogger = $logger ?: function($message){
            Log::info($message);
        };
    }

    /**
     * Set the logger environment.
     *
     * @param callable $callback
     * @return void
     */
    public static function threadLogger(?callable $logger = null)
    {
        static::$threadLogger = $logger ?: function($message){
            Log::channel('stdout')->info($message);
        };

        // setup stdout channel
        if (empty($logger)) {
            $channels = Config::get('logging.channels');

            if (empty($channels['stdout'])) {
                $channels['stdout'] = [
                    'driver' => 'monolog',
                    'handler' => 'Monolog\Handler\StreamHandler',
                    'formatter' => null,
                    'with' => [
                        'stream' => 'php://stdout'
                    ]
                ];

                Config::set('logging.channels', $channels);
            }
        }
    }
}
