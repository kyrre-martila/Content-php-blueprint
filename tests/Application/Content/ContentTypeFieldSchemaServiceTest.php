<?php

declare(strict_types=1);

use App\Application\Content\ContentTypeFieldSchemaService;

it('builds field objects from extracted post rows', function (): void {
    $service = new ContentTypeFieldSchemaService();

    $rows = $service->extractFromPost([
        'field_name' => ['title', 'category'],
        'field_label' => ['Title', 'Category'],
        'field_type' => ['text', 'select'],
        'field_default_value' => ['', 'news'],
        'field_placeholder' => ['Headline', ''],
        'field_options' => ['', "news\nupdates"],
        'field_required' => ['0' => '1'],
    ]);

    $errors = $service->validate($rows);
    expect($errors)->toBe([]);

    $fields = $service->buildFieldObjects($rows);

    expect($fields)->toHaveCount(2)
        ->and($fields[0]->name())->toBe('title')
        ->and($fields[0]->isRequired())->toBeTrue()
        ->and($fields[0]->settings())->toBe(['placeholder' => 'Headline'])
        ->and($fields[1]->settings())->toBe(['options' => ['news', 'updates']]);
});

it('rejects duplicate field names', function (): void {
    $service = new ContentTypeFieldSchemaService();

    $errors = $service->validate($service->extractFromPost([
        'field_name' => ['summary', 'summary'],
        'field_label' => ['Summary', 'Summary 2'],
        'field_type' => ['text', 'textarea'],
    ]));

    expect($errors['fields.1.name'] ?? null)->toBe('Field name "summary" is duplicated.');
});

it('rejects invalid field type config', function (): void {
    $service = new ContentTypeFieldSchemaService();

    $errors = $service->validate($service->extractFromPost([
        'field_name' => ['score'],
        'field_label' => ['Score'],
        'field_type' => ['number'],
        'field_placeholder' => ['not-allowed'],
    ]));

    expect($errors['fields.0.settings'] ?? null)->toBe('Number fields support only min and max settings.');
});
