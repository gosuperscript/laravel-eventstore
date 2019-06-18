<?php

namespace DigitalRisks\LaravelEventStore\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use DigitalRisks\LaravelEventStore\ServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
