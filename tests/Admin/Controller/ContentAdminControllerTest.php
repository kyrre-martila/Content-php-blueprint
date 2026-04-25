<?php

declare(strict_types=1);

use App\Admin\Controller\ContentAdminController;
use App\Admin\Security\EditorSafeContentPolicy;
use App\Application\Content\CreateContentItem;
use App\Application\Content\ListContentItems;
use App\Application\Content\UpdateContentItem;
use App\Application\Validation\ContentItemFieldValueValidator;
use App\Application\Validation\ContentItemValidator;
use App\Domain\Auth\Role;
use App\Domain\Auth\User;
use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Content\Slug;
use App\Domain\Files\FileAsset;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Http\Request;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\View\TemplateRenderer;

it('renders editor-safe content controls for editor role', function (): void {
    $context = makeContentAdminControllerContext(Role::editor());

    $response = $context['controller']->edit(new Request('GET', '/admin/content/1/edit', [], [], [], [], [], ['id' => '1']));

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('name="title"')
        ->and($response->body())->toContain('name="slug"')
        ->and($response->body())->not->toContain('id="meta_title"')
        ->and($response->body())->not->toContain('id="add-pattern-block"')
        ->and($response->body())->not->toContain('<select id="status"');
});

it('renders full content controls for admin role', function (): void {
    $context = makeContentAdminControllerContext(Role::admin());

    $response = $context['controller']->edit(new Request('GET', '/admin/content/1/edit', [], [], [], [], [], ['id' => '1']));

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('<select id="status"')
        ->and($response->body())->toContain('id="meta_title"')
        ->and($response->body())->toContain('id="add-pattern-block"');
});

it('forbids editor from creating content records', function (): void {
    $context = makeContentAdminControllerContext(Role::editor());

    $response = $context['controller']->create(new Request('GET', '/admin/content/create', [], [], [], [], []));

    expect($response->status())->toBe(403);
});

/**
 * @return array{controller: ContentAdminController}
 */
function makeContentAdminControllerContext(Role $role): array
{
    $session = new SessionManager(['name' => 'test_content_admin_controller_' . $role->value()]);
    $authSession = new AuthSession($session);
    $authSession->login(new User(1, 'test@example.com', '$2y$10$hash', 'Test User', $role, true));

    $contentType = new ContentType(
        name: 'article',
        label: 'Article',
        defaultTemplate: 'content/article.php',
        fields: [
            new ContentTypeField(
                id: 1,
                contentTypeId: 1,
                name: 'hero_title',
                label: 'Hero Title',
                fieldType: 'text',
                isRequired: true,
                defaultValue: null,
                settings: [],
                sortOrder: 0,
                createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
                updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00')
            ),
        ],
        viewType: ContentViewType::SINGLE
    );

    $item = new ContentItem(
        id: 1,
        type: $contentType,
        title: 'Editor Safe Item',
        slug: Slug::fromString('editor-safe-item'),
        status: ContentStatus::Draft,
        createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        patternBlocks: [['pattern' => 'hero', 'data' => ['headline' => 'Hello']]],
        fieldValues: ['hero_title' => 'Hello']
    );

    $contentTypes = new class($contentType) implements ContentTypeRepositoryInterface {
        public function __construct(private readonly ContentType $contentType) {}
        public function save(ContentType $contentType): ContentType { return $contentType; }
        public function findByName(string $name): ?ContentType { return $name === $this->contentType->name() ? $this->contentType : null; }
        public function findAll(): array { return [$this->contentType]; }
        public function getAllowedCategoryGroups(ContentType $type): array { return []; }
        public function attachCategoryGroup(ContentType $type, CategoryGroup $group): void {}
        public function detachCategoryGroup(ContentType $type, CategoryGroup $group): void {}
        public function remove(ContentType $contentType): void {}
    };

    $contentItems = new class($item) implements ContentItemRepositoryInterface {
        public function __construct(private ContentItem $item) {}
        public function save(ContentItem $contentItem): ContentItem { $this->item = $contentItem; return $contentItem; }
        public function findById(int $id): ?ContentItem { return $id === 1 ? $this->item : null; }
        public function findBySlug(Slug $slug): ?ContentItem { return $slug->value() === $this->item->slug()->value() ? $this->item : null; }
        public function findChildrenOf(int $parentId): array { return []; }
        public function findRootItems(): array { return [$this->item]; }
        public function findByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [$this->item], 'total_count' => 1, 'limit' => $limit, 'offset' => $offset]; }
        public function findAllWithTypes(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [$this->item->type()->name() => [$this->item]], 'total_count' => 1, 'limit' => $limit, 'offset' => $offset]; }
        public function findPublished(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function findPublishedByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function findPublishedByCategory(Category $category, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function remove(ContentItem $contentItem): void {}
    };

    $files = new class implements FileRepositoryInterface {
        public function save(FileAsset $fileAsset): FileAsset { return $fileAsset; }
        public function findById(int $id): ?FileAsset { return null; }
        public function findBySlug(string $slug): ?FileAsset { return null; }
        public function findAll(): array { return []; }
        public function delete(FileAsset $fileAsset): void {}
    };

    $controller = new ContentAdminController(
        new TemplateRenderer(__DIR__ . '/../../../templates'),
        $contentTypes,
        $contentItems,
        $files,
        new ListContentItems($contentItems),
        new CreateContentItem($contentItems, $contentTypes, new ContentItemValidator(), new ContentItemFieldValueValidator($files)),
        new UpdateContentItem($contentItems, $contentTypes, new ContentItemValidator(), new ContentItemFieldValueValidator($files)),
        new PatternRegistry(__DIR__ . '/../../../patterns'),
        $authSession,
        $session,
        new EditorSafeContentPolicy()
    );

    return ['controller' => $controller];
}
