<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

final class StructuredDataPayload
{
    /**
     * @param list<array<string, mixed>> $graph
     */
    public function __construct(
        public readonly array $graph
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->graph === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toJsonLdDocument(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => $this->graph,
        ];
    }
}
