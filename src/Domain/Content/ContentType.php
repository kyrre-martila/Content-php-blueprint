<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Content\Exception\InvalidContentTypeException;

final class ContentType
{
    /**
     * @param list<array{key: string, type: string, required: bool}>|null $fieldDefinitions
     */
    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly string $defaultTemplate,
        private readonly ?array $fieldDefinitions = null
    ) {
        $this->assertNameIsValid($name);
        $this->assertLabelIsValid($label);
        $this->assertTemplateIsValid($defaultTemplate);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function defaultTemplate(): string
    {
        return $this->defaultTemplate;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name;
    }

    /**
     * @return list<array{key: string, type: string, required: bool}>|null
     */
    public function fieldDefinitions(): ?array
    {
        return $this->fieldDefinitions;
    }

    private function assertNameIsValid(string $name): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidContentTypeException(
                'Content type name must start with a letter and contain only lowercase letters, numbers, and underscores.'
            );
        }
    }

    private function assertLabelIsValid(string $label): void
    {
        if (trim($label) === '') {
            throw new InvalidContentTypeException('Content type label cannot be empty.');
        }
    }

    private function assertTemplateIsValid(string $template): void
    {
        if (trim($template) === '') {
            throw new InvalidContentTypeException('Default template cannot be empty.');
        }

        if (str_contains($template, '..')) {
            throw new InvalidContentTypeException('Default template cannot contain path traversal segments.');
        }

        if (str_starts_with($template, '/')) {
            throw new InvalidContentTypeException('Default template must be a relative path.');
        }
    }
}
