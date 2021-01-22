<?php

return [
    'tcp_url' => env('EVENTSTORE_TCP_URL', 'tls://admin:changeit@127.0.0.1:1113'),
    'http_url' => env('EVENTSTORE_HTTP_URL', 'http://admin:changeit@127.0.0.1:2113'),
    'subscription_streams' => array_filter(explode(',', env('EVENTSTORE_SUBSCRIPTION_STREAMS'))),
    'volatile_streams' => array_filter(explode(',', env('EVENTSTORE_VOLATILE_STREAMS'))),
    'group' => env('EVENTSTORE_SUBSCRIPTION_GROUP', env('APP_NAME', 'laravel')),
    'connection' => env('EVENTSTORE_CONNECTION'),
];
