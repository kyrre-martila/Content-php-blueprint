<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Dto\ContentItemSummary;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;

final class ListContentItems
{
    public function __construct(private readonly ContentItemRepositoryInterface $contentItems)
    {
    }

    /**
     * @return array{
     *   items: list<ContentItemSummary>,
     *   total_count: int,
     *   limit: int,
     *   offset: int
     * }
     */
    public function execute(int $limit = ContentItemRepositoryInterface::DEFAULT_LIMIT, int $offset = ContentItemRepositoryInterface::DEFAULT_OFFSET): array
    {
        $result = $this->contentItems->findAllWithTypes($limit, $offset);
        $summaries = [];

        foreach ($result['items'] as $items) {
            foreach ($items as $item) {
                $id = $item->id();

                if ($id === null) {
                    continue;
                }

                $summaries[] = new ContentItemSummary(
                    $id,
                    $item->title(),
                    $item->slug()->value(),
                    $item->status()->value,
                    $item->type()->label(),
                    $item->updatedAt()->format('Y-m-d H:i:s')
                );
            }
        }

        usort(
            $summaries,
            static fn (ContentItemSummary $a, ContentItemSummary $b): int => strcmp($b->updatedAt, $a->updatedAt)
        );

        return [
            'items' => $summaries,
            'total_count' => $result['total_count'],
            'limit' => $result['limit'],
            'offset' => $result['offset'],
        ];
    }
}
