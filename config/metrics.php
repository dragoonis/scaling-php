<?php

return [

    'default' => env('METRICS_COLLECTOR', 'prometheus'),

    'collectors' => [

        'prometheus' => [
            'type' => 'prometheus',
            'namespace' => env('METRICS_NAMESPACE', 'app'),
        ],

        'statsd' => [
            'type' => 'statsd',
            'host' => env('STATSD_HOST', '127.0.0.1'),
            'port' => (int) env('STATSD_PORT', 8125),
        ],

        'log' => [
            'type' => 'logger',
            'channel' => env('METRICS_LOG_CHANNEL'),
        ],

        'null' => [
            'type' => 'null',
        ],

    ],

    'prometheus' => [

        'storage' => env('METRICS_PROMETHEUS_STORAGE', 'redis'),

        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('METRICS_REDIS_DB', 2),
            'timeout' => 0.1,
            'read_timeout' => '10',
            'persistent_connections' => false,
        ],

    ],

];
