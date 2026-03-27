<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

final class PageMeta
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $ogImage,
        public readonly ?string $canonical,
        public readonly string $ogType,
        public readonly string $twitterCard
    ) {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function fromArray(array $meta): self
    {
        return new self(
            self::normalizeString($meta['title'] ?? null),
            self::normalizeString($meta['description'] ?? null),
            self::normalizeString($meta['og_image'] ?? null),
            self::normalizeString($meta['canonical'] ?? null),
            self::normalizeString($meta['og_type'] ?? null) ?? 'website',
            self::normalizeString($meta['twitter_card'] ?? null) ?? 'summary_large_image'
        );
    }

    private static function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
