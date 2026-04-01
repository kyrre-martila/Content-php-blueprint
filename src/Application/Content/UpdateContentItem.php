<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Dto\ContentItemInput;
use App\Application\Content\Dto\ContentItemValidationResult;
use App\Application\Validation\ContentItemFieldValueValidator;
use App\Application\Validation\ContentItemValidator;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Content\Slug;
use DateTimeImmutable;

final class UpdateContentItem
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemValidator $validator,
        private readonly ContentItemFieldValueValidator $fieldValueValidator
    ) {
    }

    public function execute(int $id, ContentItemInput $input): ContentItemValidationResult
    {
        $existing = $this->contentItems->findById($id);
        if ($existing === null) {
            return ContentItemValidationResult::invalid(['general' => 'Content item was not found.']);
        }

        $errors = [];
        $validationResult = $this->validator->validate($input);
        if (!$validationResult->isValid) {
            $errors = array_merge($errors, $validationResult->errors);
        }

        $contentTypeKey = trim($input->contentType);
        $contentType = $contentTypeKey !== '' ? $this->contentTypes->findByName($contentTypeKey) : null;
        if ($contentTypeKey === '') {
            $errors['content_type'] = 'Content type is required.';
        } elseif ($contentType === null) {
            $errors['content_type'] = 'Selected content type does not exist.';
        }

        $fieldValues = [];
        if ($contentType !== null) {
            $fieldValidation = $this->fieldValueValidator->validate($contentType, $input->fieldValues);
            $fieldValues = $fieldValidation->values['field_values'] ?? [];
            if (!$fieldValidation->isValid) {
                $errors = array_merge($errors, $fieldValidation->errors);
            }
        }

        $title = $validationResult->values['title'] ?? trim($input->title);
        $slug = $validationResult->values['slug'] ?? null;
        $status = $validationResult->values['status'] ?? null;

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
            $input->patternBlocks,
            $fieldValues,
            $this->normalizeNullableText($input->metaTitle),
            $this->normalizeNullableText($input->metaDescription),
            $this->normalizeNullableText($input->ogImage),
            $this->normalizeNullableText($input->canonicalUrl),
            $input->noindex
        );

        return ContentItemValidationResult::valid($this->contentItems->save($updatedContentItem));
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
