<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use RuntimeException;

final class MySqlFileRepository implements FileRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(FileAsset $fileAsset): FileAsset
    {
        if ($fileAsset->id() === null) {
            return $this->create($fileAsset);
        }

        return $this->update($fileAsset);
    }

    public function findById(int $id): ?FileAsset
    {
        $row = $this->connection->fetchOne('SELECT * FROM files WHERE id = :id LIMIT 1', ['id' => $id]);

        return $row === null ? null : $this->mapRowToFileAsset($row);
    }

    public function findBySlug(string $slug): ?FileAsset
    {
        $row = $this->connection->fetchOne('SELECT * FROM files WHERE slug = :slug LIMIT 1', ['slug' => $slug]);

        return $row === null ? null : $this->mapRowToFileAsset($row);
    }

    private function create(FileAsset $fileAsset): FileAsset
    {
        $id = $this->connection->insertAndGetId(
            'INSERT INTO files (
                original_name,
                stored_name,
                slug,
                mime_type,
                extension,
                size_bytes,
                visibility,
                storage_disk,
                storage_path,
                checksum_sha256,
                uploaded_by_user_id,
                created_at,
                updated_at
            ) VALUES (
                :original_name,
                :stored_name,
                :slug,
                :mime_type,
                :extension,
                :size_bytes,
                :visibility,
                :storage_disk,
                :storage_path,
                :checksum_sha256,
                :uploaded_by_user_id,
                :created_at,
                :updated_at
            )',
            $this->toPersistenceArray($fileAsset)
        );

        $created = $this->findById((int) $id);

        if ($created === null) {
            throw new RuntimeException('Failed to reload file asset after insert.');
        }

        return $created;
    }

    private function update(FileAsset $fileAsset): FileAsset
    {
        $this->connection->execute(
            'UPDATE files SET
                original_name = :original_name,
                stored_name = :stored_name,
                slug = :slug,
                mime_type = :mime_type,
                extension = :extension,
                size_bytes = :size_bytes,
                visibility = :visibility,
                storage_disk = :storage_disk,
                storage_path = :storage_path,
                checksum_sha256 = :checksum_sha256,
                uploaded_by_user_id = :uploaded_by_user_id,
                created_at = :created_at,
                updated_at = :updated_at
             WHERE id = :id',
            ['id' => $fileAsset->id()] + $this->toPersistenceArray($fileAsset)
        );

        return $fileAsset;
    }

    /** @return array<string, mixed> */
    private function toPersistenceArray(FileAsset $fileAsset): array
    {
        return [
            'original_name' => $fileAsset->originalName(),
            'stored_name' => $fileAsset->storedName(),
            'slug' => $fileAsset->slug(),
            'mime_type' => $fileAsset->mimeType(),
            'extension' => $fileAsset->extension(),
            'size_bytes' => $fileAsset->sizeBytes(),
            'visibility' => $fileAsset->visibility()->value,
            'storage_disk' => $fileAsset->storageDisk(),
            'storage_path' => $fileAsset->storagePath(),
            'checksum_sha256' => $fileAsset->checksumSha256(),
            'uploaded_by_user_id' => $fileAsset->uploadedByUserId(),
            'created_at' => $fileAsset->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $fileAsset->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    /** @param array<string,mixed> $row */
    private function mapRowToFileAsset(array $row): FileAsset
    {
        return new FileAsset(
            id: $this->rowInt($row, 'id'),
            originalName: $this->rowString($row, 'original_name'),
            storedName: $this->rowString($row, 'stored_name'),
            slug: $this->rowString($row, 'slug'),
            mimeType: $this->rowString($row, 'mime_type'),
            extension: $this->rowString($row, 'extension'),
            sizeBytes: $this->rowInt($row, 'size_bytes'),
            visibility: FileVisibility::fromString($this->rowString($row, 'visibility')),
            storageDisk: $this->rowString($row, 'storage_disk'),
            storagePath: $this->rowString($row, 'storage_path'),
            checksumSha256: $this->rowNullableString($row, 'checksum_sha256'),
            uploadedByUserId: $this->rowNullableInt($row, 'uploaded_by_user_id'),
            createdAt: new DateTimeImmutable($this->rowString($row, 'created_at')),
            updatedAt: new DateTimeImmutable($this->rowString($row, 'updated_at'))
        );
    }

    /** @param array<string,mixed> $row */
    private function rowString(array $row, string $key): string
    {
        if (!array_key_exists($key, $row) || !is_scalar($row[$key])) {
            throw new RuntimeException(sprintf('Expected scalar value for files.%s', $key));
        }

        return (string) $row[$key];
    }

    /** @param array<string,mixed> $row */
    private function rowInt(array $row, string $key): int
    {
        if (!array_key_exists($key, $row) || !is_scalar($row[$key])) {
            throw new RuntimeException(sprintf('Expected scalar value for files.%s', $key));
        }

        return (int) $row[$key];
    }

    /** @param array<string,mixed> $row */
    private function rowNullableString(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row) || $row[$key] === null) {
            return null;
        }

        if (!is_scalar($row[$key])) {
            throw new RuntimeException(sprintf('Expected nullable scalar value for files.%s', $key));
        }

        return (string) $row[$key];
    }

    /** @param array<string,mixed> $row */
    private function rowNullableInt(array $row, string $key): ?int
    {
        if (!array_key_exists($key, $row) || $row[$key] === null) {
            return null;
        }

        if (!is_scalar($row[$key])) {
            throw new RuntimeException(sprintf('Expected nullable scalar value for files.%s', $key));
        }

        return (int) $row[$key];
    }
}
