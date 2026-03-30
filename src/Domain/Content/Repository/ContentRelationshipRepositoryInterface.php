<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentRelationship;
use App\Domain\Content\ContentType;

interface ContentRelationshipRepositoryInterface
{
    /**
     * @return list<array{from_slug: string, from_label: string, to_slug: string, to_label: string, relation_type: string}>
     */
    public function listRelationshipRules(): array;

    /**
     * @return list<array{direction: 'outgoing'|'incoming', from_slug: string, from_label: string, to_slug: string, to_label: string, relation_type: string}>
     */
    public function listRulesForContentType(ContentType $contentType): array;

    /**
     * @return list<array{
     *   from_item_id: int,
     *   from_item_title: string,
     *   from_item_slug: string,
     *   from_type_slug: string,
     *   from_type_label: string,
     *   to_item_id: int,
     *   to_item_title: string,
     *   to_item_slug: string,
     *   to_type_slug: string,
     *   to_type_label: string,
     *   relation_type: string,
     *   sort_order: int
     * }>
     */
    public function inspectRelationshipsForItem(ContentItem $item): array;

    /**
     * @return list<ContentRelationship>
     */
    public function findOutgoingRelationships(ContentItem $item): array;

    /**
     * @return list<ContentRelationship>
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
