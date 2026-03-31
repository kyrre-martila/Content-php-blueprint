<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentTypeField;

interface ContentTypeFieldRepositoryInterface
{
    /**
     * @param list<ContentTypeField> $fields
     */
    public function replaceForContentType(int $contentTypeId, array $fields): void;

    /**
     * @return list<ContentTypeField>
     */
    public function findByContentTypeId(int $contentTypeId): array;
}
