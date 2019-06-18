<?php

namespace DigitalRisks\LaravelEventStore;

class Client extends \GuzzleHttp\Client
{
    public function __construct(array $config = [])
    {
        $url = config('eventstore.http_url');

        $config = array_merge([
            'base_uri' => $url,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ], $config);

        parent::__construct($config);
    }
}
