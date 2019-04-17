<?php

namespace Mannum\EventStore;

use Illuminate\Support\ServiceProvider as Provider;
use Mannum\EventStore\Console\Commands\EventStoreWorker;

class ServiceProvider extends Provider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EventStoreWorker::class,
            ]);
        }
    }
}