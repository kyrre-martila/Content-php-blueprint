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

final class CreateContentItem
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemValidator $validator,
        private readonly ContentItemFieldValueValidator $fieldValueValidator
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
            $input->patternBlocks,
            $fieldValues,
            $this->normalizeNullableText($input->metaTitle),
            $this->normalizeNullableText($input->metaDescription),
            $this->normalizeNullableText($input->ogImage),
            $this->normalizeNullableText($input->canonicalUrl),
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
