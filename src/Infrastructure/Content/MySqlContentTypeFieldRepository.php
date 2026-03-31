<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentTypeField;
use App\Domain\Content\Repository\ContentTypeFieldRepositoryInterface;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use JsonException;
use RuntimeException;

final class MySqlContentTypeFieldRepository implements ContentTypeFieldRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function replaceForContentType(int $contentTypeId, array $fields): void
    {
        $this->connection->execute(
            'DELETE FROM content_type_fields WHERE content_type_id = :content_type_id',
            ['content_type_id' => $contentTypeId]
        );

        foreach ($fields as $field) {
            $settingsJson = null;
            $settings = $field->settings();
            if ($settings !== null) {
                try {
                    $settingsJson = json_encode($settings, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException('Unable to encode field settings JSON.', 0, $exception);
                }
            }

            $this->connection->execute(
                'INSERT INTO content_type_fields
                 (content_type_id, name, label, field_type, is_required, default_value, settings_json, sort_order, created_at, updated_at)
                 VALUES
                 (:content_type_id, :name, :label, :field_type, :is_required, :default_value, :settings_json, :sort_order, :created_at, :updated_at)',
                [
                    'content_type_id' => $contentTypeId,
                    'name' => $field->name(),
                    'label' => $field->label(),
                    'field_type' => $field->fieldType(),
                    'is_required' => $field->isRequired() ? 1 : 0,
                    'default_value' => $field->defaultValue(),
                    'settings_json' => $settingsJson,
                    'sort_order' => $field->sortOrder(),
                    'created_at' => $field->createdAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $field->updatedAt()->format('Y-m-d H:i:s'),
                ]
            );
        }
    }

    public function findByContentTypeId(int $contentTypeId): array
    {
        $rows = $this->connection->fetchAll(
            'SELECT id, content_type_id, name, label, field_type, is_required, default_value, settings_json, sort_order, created_at, updated_at
             FROM content_type_fields
             WHERE content_type_id = :content_type_id
             ORDER BY sort_order ASC, id ASC',
            ['content_type_id' => $contentTypeId]
        );

        return array_map(fn (array $row): ContentTypeField => $this->mapRowToField($row), $rows);
    }

    /** @param array<string,mixed> $row */
    private function mapRowToField(array $row): ContentTypeField
    {
        $settings = null;
        $settingsJson = $row['settings_json'] ?? null;

        if (is_string($settingsJson) && $settingsJson !== '') {
            try {
                $decoded = json_decode($settingsJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('Unable to decode content type field settings JSON.', 0, $exception);
            }

            if (!is_array($decoded)) {
                throw new RuntimeException('Decoded content type field settings must be an object-like JSON document.');
            }

            $settings = $decoded;
        }

        return new ContentTypeField(
            id: $this->rowIntOrNull($row, 'id'),
            contentTypeId: $this->rowInt($row, 'content_type_id'),
            name: $this->rowString($row, 'name'),
            label: $this->rowString($row, 'label'),
            fieldType: $this->rowString($row, 'field_type'),
            isRequired: $this->rowBool($row, 'is_required'),
            defaultValue: isset($row['default_value']) ? (string) $row['default_value'] : null,
            settings: $settings,
            sortOrder: $this->rowInt($row, 'sort_order'),
            createdAt: new DateTimeImmutable($this->rowString($row, 'created_at')),
            updatedAt: new DateTimeImmutable($this->rowString($row, 'updated_at')),
        );
    }

    /** @param array<string,mixed> $row */
    private function rowString(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Column "%s" is missing from content_type_fields query result.', $key));
        }

        return (string) $value;
    }

    /** @param array<string,mixed> $row */
    private function rowInt(array $row, string $key): int
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new RuntimeException(sprintf('Column "%s" is not a valid integer.', $key));
        }

        return (int) $value;
    }

    /** @param array<string,mixed> $row */
    private function rowIntOrNull(array $row, string $key): ?int
    {
        if (!array_key_exists($key, $row) || $row[$key] === null) {
            return null;
        }

        return $this->rowInt($row, $key);
    }

    /** @param array<string,mixed> $row */
    private function rowBool(array $row, string $key): bool
    {
        $value = $row[$key] ?? null;

        if (is_bool($value)) {
            return $value;
        }

        if (is_scalar($value) && in_array((string) $value, ['0', '1'], true)) {
            return (string) $value === '1';
        }

        throw new RuntimeException(sprintf('Column "%s" is not a valid boolean flag.', $key));
    }
}
