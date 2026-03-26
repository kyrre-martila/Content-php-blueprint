<?php

declare(strict_types=1);

use App\Http\Kernel;
use App\Http\Request;
use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Config\ConfigLoader;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Content\MySqlContentItemRepository;
use App\Infrastructure\Content\MySqlContentTypeRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Error\ErrorHandler;
use App\Infrastructure\Logging\Logger;
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
Env::load($projectRoot . '/.env');

$logger = new Logger($projectRoot . '/storage/logs');
$errorHandler = new ErrorHandler(Env::bool('APP_DEBUG', false), $logger);
$errorHandler->register();

$configLoader = new ConfigLoader($projectRoot . '/config');
$config = new ConfigRepository($configLoader->load());

$logger->info('Bootstrapping HTTP kernel.', ['env' => (string) $config->get('app.env', 'production')]);

/** @var array<string, mixed> $sessionConfig */
$sessionConfig = $config->get('app.session', []);
/** @var string $migrationsTable */
$migrationsTable = (string) $config->get('database.migrations.table', 'phinxlog');

$installState = null;
$connection = null;
$installationRequired = false;

try {
    /** @var array<string, mixed> $connectionConfig */
    $connectionConfig = $config->get('database.connections.mysql', []);

    $pdo = (new PdoFactory())->create($connectionConfig);
    $connection = new Connection($pdo);
    $installState = new InstallState($connection, $migrationsTable);
} catch (\RuntimeException $runtimeException) {
    $installationRequired = true;
    $logger->warning('Database bootstrap unavailable, forcing install flow.', [
        'error' => $runtimeException->getMessage(),
    ]);
}

if ($connection === null) {
    $temporaryConnection = new Connection((new \PDO('sqlite::memory:')));
    $userRepository = new MySqlUserRepository($temporaryConnection);
    $contentItemRepository = null;
    $contentTypeRepository = null;
} else {
    $contentTypeRepository = new MySqlContentTypeRepository($connection);
    $contentItemRepository = new MySqlContentItemRepository($connection);
    $userRepository = new MySqlUserRepository($connection);
}

$kernel = new Kernel(
    $projectRoot,
    new SessionManager($sessionConfig),
    $userRepository,
    $installState,
    $contentItemRepository,
    $contentTypeRepository,
    $installationRequired,
    $migrationsTable
);

$response = $kernel->handle(Request::capture());
$response->send();
