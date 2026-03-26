<?php

declare(strict_types=1);

use App\Infrastructure\Error\ErrorHandler;
use App\Infrastructure\Logging\Logger;

it('renders safe output in production mode and logs exception', function (): void {
    $directory = sys_get_temp_dir() . '/content-blueprint-errors-' . uniqid('', true);
    $handler = new ErrorHandler(false, new Logger($directory));

    ob_start();
    $handler->handleException(new RuntimeException('hidden message'));
    $output = ob_get_clean();

    expect($output)->toContain('Internal Server Error')
        ->and($output)->not->toContain('hidden message')
        ->and(is_file($directory . '/errors.log'))->toBeTrue();
});
