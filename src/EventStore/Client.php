<?php

namespace Mannum\EventStore;

//@todo - cater for event store being down - PT
class Client extends \GuzzleHttp\Client
{
    public function __construct(array $config = [])
    {
        $url = config('services.eventstore.web_url');

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
