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

final class UpdateContentItem
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentTypeRepositoryInterface $contentTypes
    ) {
    }

    public function execute(int $id, ContentItemInput $input): ContentItemValidationResult
    {
        $existing = $this->contentItems->findById($id);

        if ($existing === null) {
            return ContentItemValidationResult::invalid([
                'general' => 'Content item was not found.',
            ]);
        }

        $errors = [];
        $title = trim($input->title);

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        }

        $contentTypeKey = trim($input->contentType);
        $contentType = $contentTypeKey !== '' ? $this->contentTypes->findByName($contentTypeKey) : null;

        if ($contentTypeKey === '') {
            $errors['content_type'] = 'Content type is required.';
        } elseif ($contentType === null) {
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

        if ($slug instanceof Slug) {
            $duplicate = $this->contentItems->findBySlug($slug);

            if ($duplicate !== null && $duplicate->id() !== $id) {
                $errors['slug'] = 'Slug is already in use.';
            }
        }

        if ($errors !== [] || $contentType === null || !$slug instanceof Slug || !$status instanceof ContentStatus) {
            return ContentItemValidationResult::invalid($errors);
        }

        $updatedContentItem = new ContentItem(
            $existing->id(),
            $contentType,
            $title,
            $slug,
            $status,
            $existing->createdAt(),
            new DateTimeImmutable(),
            $input->patternBlocks
        );

        return ContentItemValidationResult::valid($this->contentItems->save($updatedContentItem));
    }
}
