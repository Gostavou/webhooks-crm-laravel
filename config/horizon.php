<?php

use Illuminate\Support\Str;

return [
    'name' => env('HORIZON_NAME'),

    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        'redis:high' => 60,
        'redis:default' => 60,
        'redis:bulk' => 60,
        'redis:save-lead' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'silenced_tags' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 128,

    'defaults' => [
        'supervisor-high' => [
            'connection' => 'redis',
            'queue' => ['high'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 60,
            'memory' => 128,
        ],

        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 3,
            'timeout' => 60,
            'memory' => 128,
        ],

        'supervisor-bulk' => [
            'connection' => 'redis',
            'queue' => ['bulk'],
            'balance' => 'simple',
            'processes' => 1,
            'tries' => 3,
            'timeout' => 60,
            'memory' => 128,
        ],

        'supervisor-save-lead' => [
            'connection' => 'redis',
            'queue' => ['save-lead'],
            'balance' => 'simple',
            'processes' => 1,
            'tries' => 3,
            'timeout' => 60,
            'memory' => 128,
        ],
    ],

    'environments' => [
        'local' => [
            'supervisor-high' => [],
            'supervisor-default' => [],
            'supervisor-bulk' => [],
            'supervisor-save-lead' => [],
        ],

        'production' => [
            'supervisor-high' => [],
            'supervisor-default' => [],
            'supervisor-bulk' => [],
            'supervisor-save-lead' => [],
        ],
    ],
];
