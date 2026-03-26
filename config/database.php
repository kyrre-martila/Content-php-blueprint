<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::int('DB_PORT', 3306),
            'database' => Env::required('DB_NAME'),
            'username' => Env::required('DB_USER'),
            'password' => Env::get('DB_PASS', ''),
        ],
    ],
];
