<?php

namespace Mannum\LaravelEventStore;

trait AddsHerokuMetadata
{
    /**
     * @metadata
     */
    public function collectHerokuMetadata()
    {
        return collect($_ENV)->filter(function ($value, $key) {
            return strpos($key, 'HEROKU_') === 0;
        })->toArray();
    }
}
