<?php

declare(strict_types=1);

use App\Infrastructure\Logging\Logger;

it('writes json log records to channel files', function (): void {
    $directory = sys_get_temp_dir() . '/content-blueprint-logs-' . uniqid('', true);
    $logger = new Logger($directory);

    $logger->info('booted', ['env' => 'test']);

    $logFile = $directory . '/application.log';

    expect(is_file($logFile))->toBeTrue();

    $contents = file_get_contents($logFile);

    expect($contents)->not->toBeFalse();

    $line = trim((string) $contents);
    $decoded = json_decode($line, true);

    expect($decoded)->toBeArray()
        ->and($decoded['level'] ?? null)->toBe('INFO')
        ->and($decoded['message'] ?? null)->toBe('booted')
        ->and($decoded['context']['env'] ?? null)->toBe('test');
});
