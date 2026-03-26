<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

$autoload = __DIR__ . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}

Env::load(__DIR__ . '/.env');

$adapter = Env::get('DB_CONNECTION', 'mysql');

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds' => __DIR__ . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => Env::get('DB_MIGRATIONS_TABLE', 'phinxlog'),
        'default_environment' => Env::get('PHINX_ENV', 'development'),
        'development' => [
            'adapter' => $adapter,
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'name' => Env::required('DB_NAME'),
            'user' => Env::required('DB_USER'),
            'pass' => Env::get('DB_PASS', ''),
            'port' => Env::int('DB_PORT', 3306),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
        ],
        'production' => [
            'adapter' => $adapter,
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'name' => Env::required('DB_NAME'),
            'user' => Env::required('DB_USER'),
            'pass' => Env::get('DB_PASS', ''),
            'port' => Env::int('DB_PORT', 3306),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
        ],
    ],
    'version_order' => 'creation',
];
