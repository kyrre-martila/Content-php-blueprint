<?php

declare(strict_types=1);

use App\Application\Validation\ContentItemFieldValueValidator;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;
use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;

function field(string $name, string $label, string $type, bool $required = false, ?string $default = null, ?array $settings = null): ContentTypeField
{
    $now = new DateTimeImmutable('2026-03-20 10:00:00');

    return new ContentTypeField(null, 1, $name, $label, $type, $required, $default, $settings, 0, $now, $now);
}

it('validates required fields', function (): void {
    $validator = new ContentItemFieldValueValidator();
    $type = new ContentType('article', 'Article', 'content/default.php', [
        field('summary', 'Summary', 'text', true),
    ]);

    $result = $validator->validate($type, []);

    expect($result->isValid)->toBeFalse()
        ->and($result->errors)->toHaveKey('field_values.summary');
});

it('normalizes number date and boolean values', function (): void {
    $validator = new ContentItemFieldValueValidator();
    $type = new ContentType('event', 'Event', 'content/default.php', [
        field('attendees', 'Attendees', 'number', false, null, ['min' => 0, 'max' => 100]),
        field('is_virtual', 'Is virtual', 'boolean'),
        field('starts_on', 'Starts on', 'date'),
    ]);

    $result = $validator->validate($type, [
        'attendees' => '42',
        'is_virtual' => 'yes',
        'starts_on' => '2026-04-01',
    ]);

    expect($result->isValid)->toBeTrue()
        ->and($result->values['field_values'])->toMatchArray([
            'attendees' => 42.0,
            'is_virtual' => true,
            'starts_on' => '2026-04-01',
        ]);
});

it('rejects select values that are not configured options', function (): void {
    $validator = new ContentItemFieldValueValidator();
    $type = new ContentType('article', 'Article', 'content/default.php', [
        field('category', 'Category', 'select', true, null, ['options' => ['news', 'events']]),
    ]);

    $result = $validator->validate($type, ['category' => 'random']);

    expect($result->isValid)->toBeFalse()
        ->and($result->errors['field_values.category'] ?? null)->toContain('configured options');
});

it('fills missing keys using defaults and null for backward compatibility', function (): void {
    $validator = new ContentItemFieldValueValidator();
    $type = new ContentType('article', 'Article', 'content/default.php', [
        field('summary', 'Summary', 'text', false, 'Default summary'),
        field('hero_image', 'Hero image', 'image', false),
    ]);

    $result = $validator->validate($type, []);

    expect($result->isValid)->toBeTrue()
        ->and($result->values['field_values'])->toBe([
            'summary' => 'Default summary',
            'hero_image' => null,
        ]);
});

it('accepts valid file references for image and file fields', function (): void {
    $validator = new ContentItemFieldValueValidator(fileRepositoryForValidationTests([4, 9]));
    $type = new ContentType('asset_page', 'Asset Page', 'content/default.php', [
        field('hero_image', 'Hero image', 'image', true),
        field('download', 'Download', 'file', false),
    ]);

    $result = $validator->validate($type, [
        'hero_image' => '4',
        'download' => 9,
    ]);

    expect($result->isValid)->toBeTrue()
        ->and($result->values['field_values'])->toMatchArray([
            'hero_image' => 4,
            'download' => 9,
        ]);
});

it('rejects missing required image field values', function (): void {
    $validator = new ContentItemFieldValueValidator(fileRepositoryForValidationTests([4]));
    $type = new ContentType('asset_page', 'Asset Page', 'content/default.php', [
        field('hero_image', 'Hero image', 'image', true),
    ]);

    $result = $validator->validate($type, [
        'hero_image' => '',
    ]);

    expect($result->isValid)->toBeFalse()
        ->and($result->errors)->toHaveKey('field_values.hero_image');
});

it('rejects invalid file IDs', function (): void {
    $validator = new ContentItemFieldValueValidator(fileRepositoryForValidationTests([3]));
    $type = new ContentType('asset_page', 'Asset Page', 'content/default.php', [
        field('hero_image', 'Hero image', 'image', false),
    ]);

    $result = $validator->validate($type, [
        'hero_image' => '999',
    ]);

    expect($result->isValid)->toBeFalse()
        ->and($result->errors['field_values.hero_image'] ?? null)->toContain('existing file');
});

it('keeps legacy URL strings for backward compatibility', function (): void {
    $validator = new ContentItemFieldValueValidator(fileRepositoryForValidationTests([]));
    $type = new ContentType('article', 'Article', 'content/default.php', [
        field('hero_image', 'Hero image', 'image', false),
    ]);

    $legacyUrl = 'https://legacy.example.com/image.jpg';

    $result = $validator->validate($type, ['hero_image' => $legacyUrl]);

    expect($result->isValid)->toBeTrue()
        ->and($result->values['field_values']['hero_image'] ?? null)->toBe($legacyUrl);
});

/**
 * @param list<int> $availableIds
 */
function fileRepositoryForValidationTests(array $availableIds): FileRepositoryInterface
{
    return new class ($availableIds) implements FileRepositoryInterface {
        /** @param list<int> $availableIds */
        public function __construct(private readonly array $availableIds)
        {
        }

        public function save(FileAsset $fileAsset): FileAsset
        {
            return $fileAsset;
        }

        public function findById(int $id): ?FileAsset
        {
            if (!in_array($id, $this->availableIds, true)) {
                return null;
            }

            $now = new DateTimeImmutable('2026-03-20 10:00:00');

            return new FileAsset(
                id: $id,
                originalName: 'file-' . $id . '.jpg',
                storedName: 'stored-' . $id . '.jpg',
                slug: 'file-' . $id,
                mimeType: 'image/jpeg',
                extension: 'jpg',
                sizeBytes: 1024,
                visibility: FileVisibility::Public,
                storageDisk: 'local',
                storagePath: 'uploads/file-' . $id . '.jpg',
                checksumSha256: null,
                uploadedByUserId: null,
                createdAt: $now,
                updatedAt: $now
            );
        }

        public function findBySlug(string $slug): ?FileAsset
        {
            return null;
        }

        public function findAll(): array
        {
            return [];
        }

        public function delete(FileAsset $fileAsset): void
        {
        }
    };
}
