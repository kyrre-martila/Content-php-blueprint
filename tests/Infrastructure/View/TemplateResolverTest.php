<?php

declare(strict_types=1);

use App\Domain\Content\ContentType;
use App\Domain\Content\CategoryGroup;
use App\Infrastructure\View\TemplateResolver;

it('resolves content template by content type first', function (): void {
    $templatesPath = createTemplateDirectory([
        'index.php' => '<?php',
        'content/page.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveContentTemplate(makeContentType('page')))
        ->toBe($templatesPath . '/content/page.php');
});

it('falls back to index template when content type template is missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'index.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveContentTemplate(makeContentType('article')))
        ->toBe($templatesPath . '/index.php');
});

it('resolves collection template by content type first', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
        'collections/page.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveCollectionTemplate(makeContentType('page')))
        ->toBe($templatesPath . '/collections/page.php');
});

it('falls back to system 404 when collection template is missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveCollectionTemplate(makeContentType('article')))
        ->toBe($templatesPath . '/system/404.php');
});

it('resolves category collection template by category group first', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
        'categories/blog.php' => '<?php',
        'collections/article.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveCategoryCollectionTemplate(makeCategoryGroup('blog'), makeContentType('article')))
        ->toBe($templatesPath . '/categories/blog.php');
});

it('falls back to collection template when category group template is missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
        'collections/article.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveCategoryCollectionTemplate(makeCategoryGroup('blog'), makeContentType('article')))
        ->toBe($templatesPath . '/collections/article.php');
});

it('falls back to system 404 when category and collection templates are missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveCategoryCollectionTemplate(makeCategoryGroup('blog'), makeContentType('article')))
        ->toBe($templatesPath . '/system/404.php');
});

it('resolves system template by route first', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
        'system/search.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveSystemTemplate('search'))
        ->toBe($templatesPath . '/system/search.php');
});

it('falls back to system 404 when system route template is missing', function (): void {
    $templatesPath = createTemplateDirectory([
        'system/404.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->resolveSystemTemplate('missing'))
        ->toBe($templatesPath . '/system/404.php');
});

function makeContentType(string $name): ContentType
{
    return new ContentType($name, ucfirst($name), 'index.php');
}

function makeCategoryGroup(string $slug): CategoryGroup
{
    return new CategoryGroup(
        id: 1,
        name: ucfirst($slug),
        slug: \App\Domain\Content\Slug::fromString($slug),
        description: null,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
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


it('reports template file existence for relative and templates-prefixed paths', function (): void {
    $templatesPath = createTemplateDirectory([
        'content/page.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->templateExists('content/page.php'))->toBeTrue()
        ->and($resolver->templateExists('templates/content/page.php'))->toBeTrue()
        ->and($resolver->templateExists('content/missing.php'))->toBeFalse();
});

it('builds template existence map for configured directories', function (): void {
    $templatesPath = createTemplateDirectory([
        'content/page.php' => '<?php',
        'collections/article.php' => '<?php',
        'categories/blog.php' => '<?php',
    ]);

    $resolver = new TemplateResolver($templatesPath);

    expect($resolver->templateExistsMap())
        ->toMatchArray([
            'content/page.php' => true,
            'collections/article.php' => true,
            'categories/blog.php' => true,
        ]);
});
