<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

return [
    'default' => Env::get('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::int('DB_PORT', 3306),
            'database' => Env::get('DB_NAME', ''),
            'username' => Env::get('DB_USER', ''),
            'password' => Env::get('DB_PASS', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'options' => [
                'persistent' => Env::bool('DB_PERSISTENT', false),
                'timeout' => Env::int('DB_TIMEOUT', 5),
            ],
        ],
    ],
    'migrations' => [
        'table' => Env::get('DB_MIGRATIONS_TABLE', 'phinxlog'),
    ],
];
