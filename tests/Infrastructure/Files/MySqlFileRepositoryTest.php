<?php

declare(strict_types=1);

use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Files\MySqlFileRepository;

it('saves and loads files from mysql file repository', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(
        'CREATE TABLE files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            original_name TEXT NOT NULL,
            stored_name TEXT NOT NULL,
            slug TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            extension TEXT NOT NULL,
            size_bytes INTEGER NOT NULL,
            visibility TEXT NOT NULL,
            storage_disk TEXT NOT NULL,
            storage_path TEXT NOT NULL,
            checksum_sha256 TEXT NULL,
            uploaded_by_user_id INTEGER NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $repository = new MySqlFileRepository(new Connection($pdo));

    $created = $repository->save(new FileAsset(
        id: null,
        originalName: 'handbook.docx',
        storedName: 'handbook-aabbccddeeff.docx',
        slug: 'handbook',
        mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        extension: 'docx',
        sizeBytes: 1024,
        visibility: FileVisibility::Authenticated,
        storageDisk: 'local',
        storagePath: 'aa/bb/handbook-aabbccddeeff.docx',
        checksumSha256: str_repeat('a', 64),
        uploadedByUserId: null,
        createdAt: new DateTimeImmutable('2026-04-09 12:00:00'),
        updatedAt: new DateTimeImmutable('2026-04-09 12:00:00')
    ));

    $loaded = $repository->findBySlug('handbook');
    $all = $repository->findAll();

    expect($created->id())->not->toBeNull()
        ->and($loaded)->not->toBeNull()
        ->and($all)->toHaveCount(1)
        ->and($loaded?->storedName())->toBe('handbook-aabbccddeeff.docx')
        ->and($loaded?->visibility())->toBe(FileVisibility::Authenticated)
        ->and($loaded?->checksumSha256())->toBe(str_repeat('a', 64));

    $repository->delete($created);
    expect($repository->findById((int) $created->id()))->toBeNull();
});
