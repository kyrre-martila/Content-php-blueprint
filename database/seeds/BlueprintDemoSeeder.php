<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class BlueprintDemoSeeder extends AbstractSeed
{
    /**
     * Installer-only demo seeding for a self-documenting first-run experience.
     *
     * Notes for AI and human operators:
     * - Pattern blocks below intentionally show composition structure (pattern + fields).
     * - Content copy explicitly documents Editor vs Dev responsibilities.
     * - OCF/composition related pages explain why content export and composition snapshots differ.
     * - Records are safe to delete and are created idempotently (no overwrite behavior).
     */
    public function run(): void
    {
        $contentTypeId = $this->ensurePageContentType();

        $this->seedPage(
            $contentTypeId,
            'Content PHP Blueprint Demo',
            'homepage',
            [
                [
                    'pattern' => 'hero',
                    'data' => [
                        'headline' => 'Build content-first sites with clear editor/dev boundaries',
                        'subheadline' => 'This demo shows how Content PHP Blueprint models content, patterns, and composition safely.',
                        'cta_label' => 'Start Exploring',
                        'cta_url' => '/about',
                    ],
                ],
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'What is Content PHP Blueprint?',
                        'body' => 'Content PHP Blueprint is a pragmatic CMS foundation that separates structured content from rendering logic. Editors update content and pattern field values, while developers own templates, pattern implementations, and architecture.',
                    ],
                ],
                [
                    'pattern' => 'features',
                    'data' => [
                        'items' => [
                            ['title' => 'Content Modeling', 'description' => 'Define content types and stable fields that power reusable workflows.'],
                            ['title' => 'Pattern Blocks', 'description' => 'Compose pages from named patterns with explicit data payloads.'],
                            ['title' => 'Export Friendly', 'description' => 'Use OCF export for portable semantic content and composition snapshots for layout intent.'],
                        ],
                    ],
                ],
                [
                    'pattern' => 'cta',
                    'data' => [
                        'title' => 'Review the content model page',
                        'body' => 'See how content type schema, composition JSON, and export boundaries work together.',
                        'button_label' => 'Open Content Model Guide',
                        'button_url' => '/content-model',
                    ],
                ],
            ]
        );

        $this->seedPage(
            $contentTypeId,
            'About This Demo Site',
            'about',
            [
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'Why this demo exists',
                        'body' => 'The installer seeds this demo so teams can inspect realistic content immediately after setup. Every page is safe to delete and is designed to teach Blueprint architecture through real examples rather than lorem ipsum.',
                    ],
                ],
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'Safe-to-delete policy',
                        'body' => 'Demo records are created only when the install flow runs and only when their slug is unused. Existing content is never overwritten, so deleting these records later will not break user-created content.',
                    ],
                ],
            ]
        );

        $this->seedPage(
            $contentTypeId,
            'Pattern Blocks in Practice',
            'patterns',
            [
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'How pattern blocks work',
                        'body' => 'Each block stores a pattern name and a fields object. The pattern name maps to developer-authored rendering code, and fields provide editor-managed values. This keeps composition explicit while still giving editors flexibility.',
                    ],
                ],
                [
                    'pattern' => 'features',
                    'data' => [
                        'items' => [
                            ['title' => 'Pattern', 'description' => 'A stable identifier such as hero or text-block.'],
                            ['title' => 'Fields', 'description' => 'A JSON payload with values expected by that pattern.'],
                            ['title' => 'Order', 'description' => 'Page composition is the ordered list of these blocks.'],
                        ],
                    ],
                ],
            ]
        );

        $this->seedPage(
            $contentTypeId,
            'Editor Mode Responsibilities',
            'editor-mode',
            [
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'What Editor Mode edits',
                        'body' => 'Editor Mode is for safe content updates: titles, copy, and pattern field values. It does not change PHP templates, pattern definitions, database schema, or infrastructure behavior.',
                    ],
                ],
                [
                    'pattern' => 'cta',
                    'data' => [
                        'title' => 'Try an inline edit workflow',
                        'body' => 'Edit a headline or paragraph value and observe that only content data changes.',
                        'button_label' => 'Compare with Dev Mode',
                        'button_url' => '/dev-mode',
                    ],
                ],
            ]
        );

        $this->seedPage(
            $contentTypeId,
            'Dev Mode Responsibilities',
            'dev-mode',
            [
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'What Dev Mode edits',
                        'body' => 'Dev Mode is for structural and implementation work: adding content types, changing field schemas, creating patterns, editing templates, and evolving composition tooling. Developers define capabilities; editors populate them.',
                    ],
                ],
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'Boundary with Editor Mode',
                        'body' => 'When teams protect this boundary, content operations stay fast and safe while code changes remain intentional and reviewable.',
                    ],
                ],
            ]
        );

        $this->seedPage(
            $contentTypeId,
            'Content Model, Composition, and OCF',
            'content-model',
            [
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'Composition snapshot structure',
                        'body' => 'A composition snapshot captures page-level rendering intent, including pattern ordering and field payloads. It helps teams inspect layout state or debug composition changes over time.',
                    ],
                ],
                [
                    'pattern' => 'text-block',
                    'data' => [
                        'title' => 'What OCF export represents',
                        'body' => 'OCF export is portable semantic content. It focuses on content types, item identity, and core fields for interoperability. It intentionally excludes template implementation details and UI-specific composition concerns.',
                    ],
                ],
                [
                    'pattern' => 'features',
                    'data' => [
                        'items' => [
                            ['title' => 'Modeling', 'description' => 'Content type "page" with title/slug/status plus pattern-based composition.'],
                            ['title' => 'Composition', 'description' => 'Ordered pattern blocks with realistic field values.'],
                            ['title' => 'Portability', 'description' => 'OCF export keeps the semantic layer portable across systems.'],
                        ],
                    ],
                ],
            ]
        );
    }

    private function ensurePageContentType(): int
    {
        $existing = $this->fetchRow('SELECT id FROM content_types WHERE slug = :slug LIMIT 1', ['slug' => 'page']);

        if (is_array($existing) && isset($existing['id'])) {
            return (int) $existing['id'];
        }

        $this->execute(
            <<<'SQL'
INSERT INTO content_types (name, slug, description, created_at, updated_at)
VALUES (:name, :slug, :description, NOW(), NOW())
SQL,
            [
                'name' => 'Page',
                'slug' => 'page',
                'description' => 'content/default.php',
            ]
        );

        $contentType = $this->fetchRow('SELECT id FROM content_types WHERE slug = :slug LIMIT 1', ['slug' => 'page']);

        if (!is_array($contentType) || !isset($contentType['id'])) {
            throw new RuntimeException('Unable to create or fetch page content type.');
        }

        $contentTypeId = (int) $contentType['id'];

        // These fields map to the baseline semantic model expected by admin/editor tooling.
        $this->ensureField($contentTypeId, 'Title', 'title', 'text', true, 1);
        $this->ensureField($contentTypeId, 'Slug', 'slug', 'slug', true, 2);
        $this->ensureField($contentTypeId, 'Pattern Blocks', 'pattern_blocks', 'json', false, 3);
        $this->ensureField($contentTypeId, 'Status', 'status', 'select', true, 4, ['options' => ['draft', 'published']]);

        return $contentTypeId;
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    private function ensureField(
        int $contentTypeId,
        string $name,
        string $fieldKey,
        string $fieldType,
        bool $required,
        int $sortOrder,
        ?array $settings = null
    ): void {
        $existing = $this->fetchRow(
            'SELECT id FROM content_fields WHERE content_type_id = :content_type_id AND field_key = :field_key LIMIT 1',
            [
                'content_type_id' => $contentTypeId,
                'field_key' => $fieldKey,
            ]
        );

        if (is_array($existing) && isset($existing['id'])) {
            return;
        }

        $this->execute(
            <<<'SQL'
INSERT INTO content_fields (content_type_id, name, field_key, field_type, is_required, sort_order, settings_json, created_at, updated_at)
VALUES (:content_type_id, :name, :field_key, :field_type, :is_required, :sort_order, :settings_json, NOW(), NOW())
SQL,
            [
                'content_type_id' => $contentTypeId,
                'name' => $name,
                'field_key' => $fieldKey,
                'field_type' => $fieldType,
                'is_required' => $required,
                'sort_order' => $sortOrder,
                'settings_json' => $settings !== null ? json_encode($settings, JSON_UNESCAPED_SLASHES) : null,
            ]
        );
    }

    /**
     * @param list<array{pattern: string, data: array<string, mixed>}> $patternBlocks
     */
    private function seedPage(int $contentTypeId, string $title, string $slug, array $patternBlocks): void
    {
        $existing = $this->fetchRow('SELECT id FROM content_items WHERE slug = :slug LIMIT 1', ['slug' => $slug]);

        if (is_array($existing) && isset($existing['id'])) {
            return;
        }

        $encodedBlocks = json_encode($patternBlocks, JSON_UNESCAPED_SLASHES);

        $this->execute(
            <<<'SQL'
INSERT INTO content_items (content_type_id, author_user_id, title, slug, status, body, pattern_blocks, published_at, created_at, updated_at)
VALUES (:content_type_id, NULL, :title, :slug, 'published', :body, :pattern_blocks, NOW(), NOW(), NOW())
SQL,
            [
                'content_type_id' => $contentTypeId,
                'title' => $title,
                'slug' => $slug,
                'body' => null,
                'pattern_blocks' => $encodedBlocks,
            ]
        );
    }
}
