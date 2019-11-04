<?php

namespace DigitalRisks\LaravelEventStore;

use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreReset;
use DigitalRisks\LaravelEventStore\Console\Commands\EventStoreWorker;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\EventStore;
use DigitalRisks\LaravelEventStore\Listeners\SendToEventStoreListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/eventstore.php' => config_path('eventstore.php'),
            ], 'config');

            $this->commands([
                EventStoreWorker::class,
                EventStoreReset::class,
            ]);
        }

        $this->eventClasses();
        $this->events();
        $this->logger();

        Event::listen(ShouldBeStored::class, SendToEventStoreListener::class);
    }

    /**
     * Set the eventToClass method.
     *
     * @return void
     */
    public function eventClasses()
    {
        EventStore::eventToClass();
    }

    /**
     * Handle logging when event is triggered.
     *
     * @return void
     */
    public function logger()
    {
        EventStore::logger();
    }

    /**
     * Handle event events.
     *
     * @return void
     */
    public function events()
    {
        EventStore::onStart();
        EventStore::onSuccess();
        EventStore::onError();
        EventStore::onFinish();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/eventstore.php', 'eventstore');

        $this->app->singleton(EventStore::class, function () {
            return new EventStore;
        });
    }
}
