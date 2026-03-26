<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Dto\ContentItemSummary;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;

final class ListContentItems
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentTypeRepositoryInterface $contentTypes
    ) {
    }

    /**
     * @return list<ContentItemSummary>
     */
    public function execute(): array
    {
        $summaries = [];

        foreach ($this->contentTypes->findAll() as $contentType) {
            foreach ($this->contentItems->findByType($contentType) as $item) {
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

        return $summaries;
    }
}
