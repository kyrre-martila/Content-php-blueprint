<?php

declare(strict_types=1);

use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;
use App\Domain\Content\Exception\InvalidContentTypeException;
use App\Domain\Content\Exception\InvalidContentTypeFieldException;

it('rejects unsupported content type field types', function (): void {
    new ContentTypeField(
        id: null,
        contentTypeId: 1,
        name: 'summary',
        label: 'Summary',
        fieldType: 'markdown',
        isRequired: true,
        defaultValue: null,
        settings: null,
        sortOrder: 0,
        createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
    );
})->throws(InvalidContentTypeFieldException::class);

it('rejects duplicate field names within a content type schema', function (): void {
    new ContentType('article', 'Article', 'content/article.php', [
        new ContentTypeField(null, 1, 'summary', 'Summary', 'text', true, null, null, 0, new DateTimeImmutable('2026-03-20 10:00:00'), new DateTimeImmutable('2026-03-20 10:00:00')),
        new ContentTypeField(null, 1, 'summary', 'Short Summary', 'textarea', false, null, null, 1, new DateTimeImmutable('2026-03-20 10:00:00'), new DateTimeImmutable('2026-03-20 10:00:00')),
    ]);
})->throws(InvalidContentTypeException::class);
