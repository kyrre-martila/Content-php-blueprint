<?php

declare(strict_types=1);

use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;

it('allows only expected visibility values', function (): void {
    expect(FileVisibility::fromString('public'))->toBe(FileVisibility::Public)
        ->and(FileVisibility::fromString('authenticated'))->toBe(FileVisibility::Authenticated)
        ->and(FileVisibility::fromString('private'))->toBe(FileVisibility::Private);

    FileVisibility::fromString('team-only');
})->throws(ValueError::class);

it('rejects invalid file asset invariants', function (): void {
    new FileAsset(
        id: null,
        originalName: 'Example.pdf',
        storedName: 'example.pdf',
        slug: 'example-file',
        mimeType: 'application/pdf',
        extension: 'pdf',
        sizeBytes: 100,
        visibility: FileVisibility::Private,
        storageDisk: 'local',
        storagePath: '../bad-path',
        checksumSha256: null,
        uploadedByUserId: null,
        createdAt: new DateTimeImmutable('2026-04-09 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-04-09 00:00:00')
    );
})->throws(InvalidArgumentException::class);
