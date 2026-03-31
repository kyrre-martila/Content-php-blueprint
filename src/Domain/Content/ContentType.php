<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Content\Exception\InvalidContentTypeException;

final class ContentType
{
    /**
     * @param list<ContentTypeField> $fields
     */
    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly string $defaultTemplate,
        private readonly array $fields = [],
        private readonly ContentViewType $viewType = ContentViewType::SINGLE,
        /** @var list<int> */
        private readonly array $allowedCategoryGroupIds = [],
    ) {
        $this->assertNameIsValid($name);
        $this->assertLabelIsValid($label);
        $this->assertTemplateIsValid($defaultTemplate);
        $this->assertFieldsHaveUniqueNames($fields);
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
     * @return list<array{key: string, type: string, required: bool}>
     */
    public function fieldDefinitions(): array
    {
        return array_map(
            static fn (ContentTypeField $field): array => $field->toPortableFieldDefinition(),
            $this->fields
        );
    }

    /**
     * @return list<ContentTypeField>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function viewType(): ContentViewType
    {
        return $this->viewType;
    }

    public function isSingleView(): bool
    {
        return $this->viewType === ContentViewType::SINGLE;
    }

    public function isCollectionView(): bool
    {
        return $this->viewType === ContentViewType::COLLECTION;
    }

    /**
     * @return list<int>
     */
    public function allowedCategoryGroupIds(): array
    {
        return $this->allowedCategoryGroupIds;
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

    /** @param list<ContentTypeField> $fields */
    private function assertFieldsHaveUniqueNames(array $fields): void
    {
        $seen = [];

        foreach ($fields as $field) {
            $name = $field->name();

            if (isset($seen[$name])) {
                throw new InvalidContentTypeException(sprintf('Field name "%s" must be unique within a content type.', $name));
            }

            $seen[$name] = true;
        }
    }
}
