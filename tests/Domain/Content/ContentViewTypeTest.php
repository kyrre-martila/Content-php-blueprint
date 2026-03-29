<?php

declare(strict_types=1);

use App\Domain\Content\ContentViewType;
use App\Domain\Content\Exception\InvalidContentTypeException;

it('accepts supported content view types', function (): void {
    expect(ContentViewType::fromString('single'))->toBe(ContentViewType::SINGLE)
        ->and(ContentViewType::fromString('collection'))->toBe(ContentViewType::COLLECTION);
});

it('rejects unsupported content view types', function (): void {
    ContentViewType::fromString('grid');
})->throws(InvalidContentTypeException::class);
