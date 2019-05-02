<?php

namespace Mannum\LaravelEventStore\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Mannum\LaravelEventStore\ServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
