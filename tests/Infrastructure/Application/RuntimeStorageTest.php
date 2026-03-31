<?php

declare(strict_types=1);

use App\Infrastructure\Application\RuntimeStorage;

it('creates required runtime storage directories when missing', function (): void {
    $projectRoot = sys_get_temp_dir() . '/content-blueprint-runtime-' . uniqid('', true);

    if (!mkdir($projectRoot, 0775, true) && !is_dir($projectRoot)) {
        throw new RuntimeException('Unable to create temporary project root for test.');
    }

    RuntimeStorage::ensure($projectRoot);

    foreach (RuntimeStorage::requiredDirectories($projectRoot) as $directory) {
        expect(is_dir($directory))->toBeTrue();
    }
});
