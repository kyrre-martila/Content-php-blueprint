<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use RuntimeException;

final class MySqlContentTypeRepository implements ContentTypeRepositoryInterface
{
    private const FALLBACK_TEMPLATE = 'content/default.php';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(ContentType $contentType): ContentType
    {
        $existingRow = $this->connection->fetchOne(
            'SELECT id FROM content_types WHERE slug = :slug LIMIT 1',
            ['slug' => $contentType->name()]
        );

        if ($existingRow === null) {
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

            $this->connection->execute(
                'INSERT INTO content_types (name, slug, description, view_type, created_at, updated_at)
                 VALUES (:name, :slug, :description, :view_type, :created_at, :updated_at)',
                [
                    'name' => $contentType->label(),
                    'slug' => $contentType->name(),
                    'description' => $contentType->defaultTemplate(),
                    'view_type' => $contentType->viewType()->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            return $contentType;
        }

        $id = $this->rowInt($existingRow, 'id');

        $affectedRows = $this->connection->execute(
            'UPDATE content_types
             SET name = :name,
                 description = :description,
                 view_type = :view_type,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'id' => $id,
                'name' => $contentType->label(),
                'description' => $contentType->defaultTemplate(),
                'view_type' => $contentType->viewType()->value,
                'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );

        if ($affectedRows < 1) {
            throw new RuntimeException('Failed to update content type record.');
        }

        return $contentType;
    }

    public function findByName(string $name): ?ContentType
    {
        $row = $this->connection->fetchOne(
            'SELECT id, name, slug, description, view_type
             FROM content_types
             WHERE slug = :slug
             LIMIT 1',
            ['slug' => $name]
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRowToContentType($row);
    }

    public function findAll(): array
    {
        $rows = $this->connection->fetchAll(
            'SELECT id, name, slug, description, view_type
             FROM content_types
             ORDER BY name ASC'
        );

        $contentTypes = [];

        foreach ($rows as $row) {
            $contentTypes[] = $this->mapRowToContentType($row);
        }

        return $contentTypes;
    }

    public function remove(ContentType $contentType): void
    {
        $affectedRows = $this->connection->execute(
            'DELETE FROM content_types WHERE slug = :slug',
            ['slug' => $contentType->name()]
        );

        if ($affectedRows < 1) {
            throw new RuntimeException(sprintf('Content type "%s" was not found for removal.', $contentType->name()));
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToContentType(array $row): ContentType
    {
        $machineName = $this->rowString($row, 'slug');
        $label = $this->rowString($row, 'name');

        $template = $row['description'] ?? null;
        $defaultTemplate = is_string($template) && trim($template) !== '' ? $template : self::FALLBACK_TEMPLATE;

        $viewType = $this->rowString($row, 'view_type');

        return new ContentType(
            $machineName,
            $label,
            $defaultTemplate,
            null,
            ContentViewType::fromString($viewType)
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowString(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Column "%s" is missing from content_types query result.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowInt(array $row, string $key): int
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new RuntimeException(sprintf('Column "%s" is not a valid integer.', $key));
        }

        return (int) $value;
    }
}
