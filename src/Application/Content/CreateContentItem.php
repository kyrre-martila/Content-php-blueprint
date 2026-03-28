<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Dto\ContentItemInput;
use App\Application\Content\Dto\ContentItemValidationResult;
use App\Domain\Content\ContentItem;
use App\Application\Validation\ContentItemValidator;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Content\Slug;
use DateTimeImmutable;

final class CreateContentItem
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemValidator $validator
    ) {
    }

    public function execute(ContentItemInput $input): ContentItemValidationResult
    {
        $errors = [];

        $contentTypeKey = trim($input->contentType);

        if ($contentTypeKey === '') {
            $errors['content_type'] = 'Content type is required.';
        }

        $contentType = $contentTypeKey !== '' ? $this->contentTypes->findByName($contentTypeKey) : null;

        if ($contentType === null && $contentTypeKey !== '') {
            $errors['content_type'] = 'Selected content type does not exist.';
        }

        $validationResult = $this->validator->validate($input);

        if (!$validationResult->isValid) {
            $errors = array_merge($errors, $validationResult->errors);
        }

        $title = $validationResult->values['title'] ?? trim($input->title);
        $slug = $validationResult->values['slug'] ?? null;
        $status = $validationResult->values['status'] ?? null;

        if ($slug instanceof Slug && $this->contentItems->findBySlug($slug) !== null) {
            $errors['slug'] = 'Slug is already in use.';
        }

        if ($errors !== [] || $contentType === null || !$slug instanceof Slug || !$status instanceof ContentStatus) {
            return ContentItemValidationResult::invalid($errors);
        }

        $now = new DateTimeImmutable();
        $metaTitle = $this->normalizeNullableText($input->metaTitle);
        $metaDescription = $this->normalizeNullableText($input->metaDescription);
        $ogImage = $this->normalizeNullableText($input->ogImage);
        $canonicalUrl = $this->normalizeNullableText($input->canonicalUrl);
        $contentItem = new ContentItem(
            null,
            $contentType,
            $title,
            $slug,
            $status,
            $now,
            $now,
            $input->patternBlocks,
            $metaTitle,
            $metaDescription,
            $ogImage,
            $canonicalUrl,
            $input->noindex
        );

        return ContentItemValidationResult::valid($this->contentItems->save($contentItem));
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
