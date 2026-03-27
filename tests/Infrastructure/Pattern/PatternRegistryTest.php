<?php

declare(strict_types=1);

use App\Infrastructure\Pattern\PatternRegistry;

it('loads patterns when metadata is valid', function (): void {
    $patternsDir = sys_get_temp_dir() . '/content-blueprint-patterns-valid-' . uniqid('', true);
    mkdir($patternsDir . '/hero', 0777, true);

    file_put_contents($patternsDir . '/hero/pattern.json', json_encode([
        'name' => 'Hero section',
        'description' => 'Large hero section with heading, text, and button',
        'category' => 'marketing',
        'fields' => [
            ['name' => 'headline', 'type' => 'text', 'label' => 'Headline'],
        ],
        'key' => 'hero',
    ], JSON_THROW_ON_ERROR));

    $registry = new PatternRegistry($patternsDir);

    expect($registry->exists('hero'))->toBeTrue();
});

it('fails fast when metadata is missing required keys', function (): void {
    $patternsDir = sys_get_temp_dir() . '/content-blueprint-patterns-invalid-' . uniqid('', true);
    mkdir($patternsDir . '/hero', 0777, true);

    file_put_contents($patternsDir . '/hero/pattern.json', json_encode([
        'name' => 'Hero section',
        'description' => 'Large hero section with heading, text, and button',
        'fields' => [
            ['name' => 'headline', 'type' => 'text', 'label' => 'Headline'],
        ],
        'key' => 'hero',
    ], JSON_THROW_ON_ERROR));

    expect(fn (): PatternRegistry => new PatternRegistry($patternsDir))
        ->toThrow(InvalidArgumentException::class, 'Pattern metadata invalid in ' . $patternsDir . '/hero/pattern.json: missing "category"');
});

it('fails fast when a field definition is malformed', function (): void {
    $patternsDir = sys_get_temp_dir() . '/content-blueprint-patterns-invalid-field-' . uniqid('', true);
    mkdir($patternsDir . '/hero', 0777, true);

    file_put_contents($patternsDir . '/hero/pattern.json', json_encode([
        'name' => 'Hero section',
        'description' => 'Large hero section with heading, text, and button',
        'category' => 'marketing',
        'fields' => [
            ['name' => 'headline', 'type' => 'text'],
        ],
        'key' => 'hero',
    ], JSON_THROW_ON_ERROR));

    expect(fn (): PatternRegistry => new PatternRegistry($patternsDir))
        ->toThrow(InvalidArgumentException::class, 'Pattern metadata invalid in ' . $patternsDir . '/hero/pattern.json: field at index 0 missing "label"');
});
