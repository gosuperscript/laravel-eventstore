<?php

return [
    'tcp_url' => env('EVENTSTORE_TCP_URL', 'tls://admin:changeit@localhost:1113'),
    'http_url' => env('EVENTSTORE_HTTP_URL', 'http://admin:changeit@localhost:2113'),
    'subscription_streams' => array_filter(explode(',', env('EVENTSTORE_SUBSCRIPTION_STREAMS'))),
    'volatile_streams' => array_filter(explode(',', env('EVENTSTORE_VOLATILE_STREAMS'))),
    'group' => env('EVENTSTORE_SUBSCRIPTION_GROUP', env('APP_NAME', 'laravel')),
];
