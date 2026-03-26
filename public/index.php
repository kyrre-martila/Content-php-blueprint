<?php

declare(strict_types=1);

use App\Http\Kernel;
use App\Http\Request;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Config\ConfigLoader;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Content\MySqlContentItemRepository;
use App\Infrastructure\Content\MySqlContentTypeRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Support\Env;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Composer autoload file not found. Run: composer install';
    exit(1);
}

require $autoload;

$projectRoot = dirname(__DIR__);

try {
    Env::load($projectRoot . '/.env');

    $configLoader = new ConfigLoader($projectRoot . '/config');
    $config = new ConfigRepository($configLoader->load());

    /** @var array<string, mixed> $connectionConfig */
    $connectionConfig = $config->get('database.connections.mysql', []);

    $pdo = (new PdoFactory())->create($connectionConfig);
    $connection = new Connection($pdo);

    $contentTypeRepository = new MySqlContentTypeRepository($connection);
    $contentItemRepository = new MySqlContentItemRepository($connection);
    $userRepository = new MySqlUserRepository($connection);

    /** @var array<string, mixed> $sessionConfig */
    $sessionConfig = $config->get('app.session', []);

    $kernel = new Kernel(
        $projectRoot,
        new SessionManager($sessionConfig),
        $userRepository,
        $contentItemRepository,
        $contentTypeRepository
    );

    $response = $kernel->handle(Request::capture());
    $response->send();
} catch (\Throwable $throwable) {
    $isDebug = Env::bool('APP_DEBUG', false);

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    if ($isDebug) {
        echo $throwable->getMessage();
        echo PHP_EOL;
        echo $throwable->getTraceAsString();
        exit(1);
    }

    error_log($throwable->getMessage());
    echo 'Application bootstrapping failed.';
    exit(1);
}
