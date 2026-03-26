<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

return [
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => Env::required('APP_URL'),
    'session' => [
        'name' => Env::get('SESSION_NAME', 'content_blueprint_session'),
        'secure_cookie' => Env::bool('SESSION_SECURE_COOKIE', false),
    ],
];
