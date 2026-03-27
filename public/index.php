<?php

declare(strict_types=1);

use App\Http\Request;
use App\Infrastructure\Application\ApplicationFactory;
use App\Infrastructure\Config\ConfigLoader;
use App\Infrastructure\Config\ConfigRepository;
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

$kernel = (new ApplicationFactory($projectRoot, $config, $logger))->createKernel();

$response = $kernel->handle(Request::capture());
$response->send();
