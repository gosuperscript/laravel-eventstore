<?php

namespace Mannum\LaravelEventStore;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mannum\LaravelEventStore\Skeleton\SkeletonClass
 */
class LaravelEventStoreFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-event-store';
    }
}
