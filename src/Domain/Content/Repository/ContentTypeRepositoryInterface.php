<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentType;

interface ContentTypeRepositoryInterface
{
    public function save(ContentType $contentType): ContentType;

    public function findByName(string $name): ?ContentType;

    /**
     * @return list<ContentType>
     */
    public function findAll(): array;

    public function remove(ContentType $contentType): void;
}
