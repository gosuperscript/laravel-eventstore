<?php

namespace DigitalRisks\LaravelEventStore\Traits;

trait AddsUserMetaData
{
    /**
     * @metadata
     */
    public function collectUserMetaData()
    {
        $user = request()->user();

        if (!$user) {
            return [];
        }

        return [
            'user' => $user->toArray()
        ];
    }
}
