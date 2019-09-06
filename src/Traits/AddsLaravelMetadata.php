<?php

namespace DigitalRisks\LaravelEventStore\Traits;

trait AddsLaravelMetadata
{
    /**
     * @metadata
     */
    public function collectLaravelMetadata()
    {
        return [
            'name' => config('app.name'),
            'env' => config('app.env'),
        ];
    }
}
