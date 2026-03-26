<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Dto\ContentItemInput;
use App\Application\Content\Dto\ContentItemValidationResult;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\Exception\InvalidSlugException;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Content\Slug;
use DateTimeImmutable;
use ValueError;

final class CreateContentItem
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentTypeRepositoryInterface $contentTypes
    ) {
    }

    public function execute(ContentItemInput $input): ContentItemValidationResult
    {
        $errors = [];
        $title = trim($input->title);

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        }

        $contentTypeKey = trim($input->contentType);

        if ($contentTypeKey === '') {
            $errors['content_type'] = 'Content type is required.';
        }

        $contentType = $contentTypeKey !== '' ? $this->contentTypes->findByName($contentTypeKey) : null;

        if ($contentType === null && $contentTypeKey !== '') {
            $errors['content_type'] = 'Selected content type does not exist.';
        }

        try {
            $slug = Slug::fromString($input->slug);
        } catch (InvalidSlugException) {
            $errors['slug'] = 'Slug is required and may only include lowercase letters, numbers, and hyphens.';
            $slug = null;
        }

        try {
            $status = ContentStatus::fromString($input->status);
        } catch (ValueError) {
            $errors['status'] = 'Status is required.';
            $status = null;
        }

        if ($slug instanceof Slug && $this->contentItems->findBySlug($slug) !== null) {
            $errors['slug'] = 'Slug is already in use.';
        }

        if ($errors !== [] || $contentType === null || !$slug instanceof Slug || !$status instanceof ContentStatus) {
            return ContentItemValidationResult::invalid($errors);
        }

        $now = new DateTimeImmutable();
        $contentItem = new ContentItem(
            null,
            $contentType,
            $title,
            $slug,
            $status,
            $now,
            $now,
            $input->patternBlocks
        );

        return ContentItemValidationResult::valid($this->contentItems->save($contentItem));
    }
}
