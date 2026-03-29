<?php

declare(strict_types=1);

use App\Application\SEO\SitemapGenerator;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Http\Controller\SitemapController;
use App\Http\Request;

it('returns application/xml output from published content items', function (): void {
    $published = sitemapControllerContentItem('about', ContentStatus::Published);

    $repository = new class ($published) implements ContentItemRepositoryInterface {
        public function __construct(private readonly ContentItem $published)
        {
        }

        public function save(ContentItem $contentItem): ContentItem
        {
            return $contentItem;
        }

        public function findById(int $id): ?ContentItem
        {
            return null;
        }

        public function findBySlug(Slug $slug): ?ContentItem
        {
            return null;
        }

        public function findByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset];
        }

        public function findAllWithTypes(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset];
        }

        public function findPublished(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [$this->published], 'total_count' => 1, 'limit' => $limit, 'offset' => $offset];
        }

        public function remove(ContentItem $contentItem): void
        {
        }
    };

    $controller = new SitemapController(
        $repository,
        new SitemapGenerator('https://contentphp.martila.no')
    );

    $response = $controller->index(new Request('GET', '/sitemap.xml', [], [], [], [], []));

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($response->status())->toBe(200)
        ->and($response->header('Content-Type'))->toBe('application/xml; charset=utf-8')
        ->and($output)->toContain('<loc>https://contentphp.martila.no/about</loc>');
});

function sitemapControllerContentItem(string $slug, ContentStatus $status): ContentItem
{
    return new ContentItem(
        id: 1,
        type: new ContentType('page', 'Page', 'content/default.php'),
        title: ucfirst($slug),
        slug: Slug::fromString($slug),
        status: $status,
        createdAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27T12:00:00+00:00')
    );
}
