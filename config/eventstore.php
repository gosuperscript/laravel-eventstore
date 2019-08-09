<?php

return [
    'tcp_url' => env('EVENTSTORE_TCP_URL', 'tls://admin:changeit@localhost:1113'),
    'http_url' => env('EVENTSTORE_HTTP_URL', 'http://admin:changeit@localhost:2113'),
    'streams' => [],
    'volatile_streams' => [],
    'group' => '',
    'namespace' => 'App\Events'
];
