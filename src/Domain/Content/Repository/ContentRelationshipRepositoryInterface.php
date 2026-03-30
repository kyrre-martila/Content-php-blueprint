<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentRelationship;
use App\Domain\Content\ContentType;
use App\Domain\Content\EnrichedContentRelationship;

interface ContentRelationshipRepositoryInterface
{
    /**
     * @return list<array{
     *   from_type: string,
     *   to_type: string,
     *   relation_type: string
     * }>
     */
    public function findRelationshipRules(): array;

    /**
     * @return list<array{
     *   from_type: string,
     *   to_type: string,
     *   relation_type: string
     * }>
     */
    public function findRelationshipRulesForContentType(ContentType $type): array;

    /**
     * @return list<EnrichedContentRelationship>
     */
    public function findOutgoingRelationships(ContentItem $item): array;

    /**
     * @return list<EnrichedContentRelationship>
     */
    public function findIncomingRelationships(ContentItem $item): array;

    /**
     * @return list<ContentRelationship>
     */
    public function findByType(ContentItem $item, string $relationType): array;

    public function attach(ContentItem $from, ContentItem $to, string $relationType, int $sortOrder = 0): void;

    public function detach(ContentItem $from, ContentItem $to, string $relationType): void;

    public function allowRelationship(ContentType $from, ContentType $to, string $relationType): void;

    public function isRelationshipAllowed(ContentType $from, ContentType $to, string $relationType): bool;

    public function removeRelationshipRule(ContentType $from, ContentType $to, string $relationType): void;
}
