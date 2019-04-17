<?php

namespace Mannum\EventStore;

use EventLoop\EventLoop;

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

    public function register()
    {
        $this->app->singleton(LoopInterface::class, function () {
            return EventLoop::getLoop();
        }); 
    }
}