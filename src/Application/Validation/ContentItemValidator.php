<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Application\Content\Dto\ContentItemInput;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\Exception\InvalidSlugException;
use App\Domain\Content\Slug;
use JsonException;
use ValueError;

final class ContentItemValidator
{
    public function validate(ContentItemInput $input): ValidationResult
    {
        $errors = [];
        $title = trim($input->title);

        if ($title === '') {
            $errors['title'] = 'Title is required.';
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

        try {
            json_encode($input->patternBlocks, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $errors['pattern_blocks'] = 'Pattern blocks must be valid JSON data.';
        }

        if ($errors !== [] || !$slug instanceof Slug || !$status instanceof ContentStatus) {
            return ValidationResult::invalid($errors, [
                'title' => $title,
            ]);
        }

        return ValidationResult::valid([
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
        ]);
    }
}
