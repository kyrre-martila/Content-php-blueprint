<?php

declare(strict_types=1);

use App\Infrastructure\Files\LocalFileStorage;

it('stores files under normalized relative paths and prevents traversal', function (): void {
    $root = sys_get_temp_dir() . '/content-blueprint-file-storage-' . uniqid('', true);
    if (!mkdir($root, 0775, true) && !is_dir($root)) {
        throw new RuntimeException('Could not create temporary storage root.');
    }

    $storage = new LocalFileStorage($root);
    $storage->write('ab/cd/manual.pdf', 'file-contents');

    expect($storage->exists('ab/cd/manual.pdf'))->toBeTrue()
        ->and($storage->read('ab/cd/manual.pdf'))->toBe('file-contents')
        ->and($storage->absolutePath('ab/cd/manual.pdf'))->toEndWith('/ab/cd/manual.pdf');

    $storage->write('../outside.txt', 'nope');
})->throws(RuntimeException::class);
