<?php

namespace DigitalRisks\LaravelEventStore;

use DigitalRisks\LaravelEventStore\Console\EventStoreWorker;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeEventStored;
use DigitalRisks\LaravelEventStore\Listeners\SendToEventStoreListener;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\Event;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/eventstore.php' => config_path('eventstore.php'),
            ], 'config');

            $this->commands([
                EventStoreWorker::class,
            ]);
        }

        Event::listen(ShouldBeEventStored::class, SendToEventStoreListener::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/eventstore.php', 'eventstore');
    }
}
