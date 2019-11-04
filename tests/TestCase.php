<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use DigitalRisks\LaravelEventStore\EventStore;
use DigitalRisks\LaravelEventStore\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        EventStore::eventToClass();

        return [
            ServiceProvider::class,
        ];
    }
}
