<?php

declare(strict_types=1);

use App\Http\Kernel;
use App\Http\Request;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Composer autoload file not found. Run: composer install";
    exit(1);
}

require $autoload;

$request = Request::capture();
$kernel = new Kernel();
$response = $kernel->handle($request);
$response->send();
