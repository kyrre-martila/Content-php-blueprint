<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\Slug;
use App\Infrastructure\View\TemplateResolver;

it('resolves explicit content item template override before content type default', function (): void {
    $templatesPath = createTemplateDirectory([
        'index.php' => '<?php',
        'content/default.php' => '<?php',
        'content/type.php' => '<?php',
        'content/override.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);
    $contentItem = makeTemplateContentItem('content/type.php', 'content/override.php');

    expect($resolver->resolveContentTemplate($contentItem))->toBe($templatesPath . '/content/override.php');
});

it('resolves content type default template when override is absent', function (): void {
    $templatesPath = createTemplateDirectory([
        'index.php' => '<?php',
        'content/default.php' => '<?php',
        'content/type.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);
    $contentItem = makeTemplateContentItem('content/type.php');

    expect($resolver->resolveContentTemplate($contentItem))->toBe($templatesPath . '/content/type.php');
});

it('falls back to content default template when content type template is missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'index.php' => '<?php',
        'content/default.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);
    $contentItem = makeTemplateContentItem('content/missing.php');

    expect($resolver->resolveContentTemplate($contentItem))->toBe($templatesPath . '/content/default.php');
});

it('falls back to index template when content default template is missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'index.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);
    $contentItem = makeTemplateContentItem('content/missing.php');

    expect($resolver->resolveContentTemplate($contentItem))->toBe($templatesPath . '/index.php');
});

function makeTemplateContentItem(string $defaultTemplate, ?string $templateOverride = null): ContentItem
{
    $now = new \DateTimeImmutable('2026-03-27 00:00:00');

    return new ContentItem(
        id: 1,
        type: new ContentType('page', 'Page', $defaultTemplate),
        title: 'Template Test',
        slug: Slug::fromString('template-test'),
        status: ContentStatus::Published,
        createdAt: $now,
        updatedAt: $now,
        templateOverride: $templateOverride
    );
}

/**
 * @param array<string, string> $files
 */
function createTemplateDirectory(array $files): string
{
    $templatesPath = sys_get_temp_dir() . '/content-blueprint-template-resolver-' . uniqid('', true);

    foreach ($files as $relativePath => $contents) {
        $fullPath = $templatesPath . '/' . $relativePath;
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($fullPath, $contents);
    }

    return $templatesPath;
}
