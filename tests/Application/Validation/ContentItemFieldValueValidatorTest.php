<?php

declare(strict_types=1);

use App\Application\Validation\ContentItemFieldValueValidator;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;

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
