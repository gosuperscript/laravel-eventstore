<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use DigitalRisks\LaravelEventStore\EventStore;
use DigitalRisks\LaravelEventStore\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Support\Facades\Log;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        EventStore::eventToClass();
        EventStore::threadLogger(function($message, $context){ return Log::info($message, $context); });

        return [
            ServiceProvider::class,
        ];
    }
}
