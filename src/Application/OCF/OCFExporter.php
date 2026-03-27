<?php

declare(strict_types=1);

namespace App\Application\OCF;

use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use JsonException;
use RuntimeException;

final class OCFExporter
{
    private const DEFAULT_EXPORT_DIRECTORY = 'storage/exports/ocf';
    private const DEFAULT_EXPORT_FILENAME = 'content-export.json';

    public function __construct(
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly string $projectRoot
    ) {
    }

    /**
     * Builds a portable OCF-aligned payload and writes it to disk.
     *
     * OCF export is content-only by design. It intentionally excludes
     * presentation/composition concerns like templates, layout logic,
     * pattern rendering details, CSS, JS, dev metadata, and repository structure.
     */
    public function exportAll(): string
    {
        $directory = $this->exportDirectoryPath();

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create OCF export directory: %s', $directory));
        }

        try {
            $encoded = json_encode($this->buildPayload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode OCF export payload as JSON.', 0, $exception);
        }

        $exportPath = $directory . '/' . self::DEFAULT_EXPORT_FILENAME;
        $result = file_put_contents($exportPath, $encoded . PHP_EOL, LOCK_EX);

        if ($result === false) {
            throw new RuntimeException(sprintf('Unable to write OCF export file: %s', $exportPath));
        }

        return $exportPath;
    }

    /**
     * @return array{
     *   export_format_version: int,
     *   ocf_version: string,
     *   generated_by: string,
     *   generated_at: string,
     *   content_types: list<array{name: string, label: string, fields: list<array{key: string, type: string, required: bool}>}>,
     *   content_items: list<array{
     *      id: int,
     *      type: string,
     *      slug: string,
     *      status: string,
     *      fields: array<string, string>,
     *      pattern_blocks: list<array{pattern: string, data: array<string, string>}>,
     *      relationships: array{content_type: string, parent_slug?: string, related_items?: list<string>},
     *      seo?: array{meta_title?: string, meta_description?: string, canonical_url?: string},
     *      metadata: array{created_at: string, updated_at: string}
     *   }>
     * }
     */
    public function buildPayload(): array
    {
        $contentTypes = [];
        $contentItems = [];

        foreach ($this->contentTypes->findAll() as $contentType) {
            $contentTypes[] = [
                'name' => $contentType->name(),
                'label' => $contentType->label(),
                'fields' => $contentType->fieldDefinitions() ?? $this->defaultPortableFieldSchema(),
            ];

            foreach ($this->contentItems->findByType($contentType) as $item) {
                $id = $item->id();

                if ($id === null) {
                    continue;
                }

                $payloadItem = [
                    'id' => $id,
                    'type' => $item->type()->name(),
                    'slug' => $item->slug()->value(),
                    'status' => $item->status()->value,
                    'fields' => [
                        'title' => $item->title(),
                    ],
                    // Structured semantic blocks are portable content data, not layout logic.
                    'pattern_blocks' => $item->patternBlocks(),
                    'relationships' => $this->relationshipsForItem($item->type()->name()),
                    'metadata' => [
                        'created_at' => $item->createdAt()->format(DATE_ATOM),
                        'updated_at' => $item->updatedAt()->format(DATE_ATOM),
                    ],
                ];

                $seo = $this->seoMetadataForItem($item->metaTitle(), $item->metaDescription(), $item->canonicalUrl());

                if ($seo !== null) {
                    $payloadItem['seo'] = $seo;
                }

                $contentItems[] = $payloadItem;
            }
        }

        return [
            'export_format_version' => 2,
            'ocf_version' => '0.1-draft',
            'generated_by' => 'content-php-blueprint',
            'generated_at' => gmdate(DATE_ATOM),
            'content_types' => $contentTypes,
            'content_items' => $contentItems,
        ];
    }

    private function exportDirectoryPath(): string
    {
        return rtrim($this->projectRoot, DIRECTORY_SEPARATOR) . '/' . self::DEFAULT_EXPORT_DIRECTORY;
    }

    /**
     * @return list<array{key: string, type: string, required: bool}>
     */
    private function defaultPortableFieldSchema(): array
    {
        return [
            ['key' => 'title', 'type' => 'string', 'required' => true],
            ['key' => 'slug', 'type' => 'slug', 'required' => true],
            ['key' => 'status', 'type' => 'string', 'required' => true],
        ];
    }

    /**
     * @return array{content_type: string, parent_slug?: string, related_items?: list<string>}
     */
    private function relationshipsForItem(string $contentType): array
    {
        return [
            'content_type' => $contentType,
        ];
    }

    /**
     * @return array{meta_title?: string, meta_description?: string, canonical_url?: string}|null
     */
    private function seoMetadataForItem(?string $metaTitle, ?string $metaDescription, ?string $canonicalUrl): ?array
    {
        $seo = [];

        if ($metaTitle !== null && trim($metaTitle) !== '') {
            $seo['meta_title'] = $metaTitle;
        }

        if ($metaDescription !== null && trim($metaDescription) !== '') {
            $seo['meta_description'] = $metaDescription;
        }

        if ($canonicalUrl !== null && trim($canonicalUrl) !== '') {
            $seo['canonical_url'] = $canonicalUrl;
        }

        return $seo === [] ? null : $seo;
    }
}
