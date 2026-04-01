<?php

declare(strict_types=1);

use App\Admin\Controller\ContentTypeAdminController;
use App\Application\Content\ContentTypeFieldSchemaService;
use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\ContentRelationshipRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplatePathMap;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

it('stores field schema through controller and service path', function (): void {
    $contentTypes = new InMemoryContentTypeRepository();
    $categoryGroups = new InMemoryCategoryGroupRepository();
    $relationships = new NullContentRelationshipRepository();

    $sessionManager = new SessionManager(['name' => 'test_content_blueprint_session']);

    $controller = new ContentTypeAdminController(
        new TemplateRenderer(__DIR__ . '/../../../templates'),
        $contentTypes,
        $categoryGroups,
        $relationships,
        new AuthSession($sessionManager),
        $sessionManager,
        new TemplateResolver(__DIR__ . '/../../../templates', new TemplatePathMap()),
        new TemplatePathMap(),
        new ContentTypeFieldSchemaService()
    );

    $request = new Request('POST', '/admin/content-types', [], [
        'name' => 'Article',
        'slug' => 'article',
        'view_type' => 'single',
        'field_name' => ['title'],
        'field_label' => ['Title'],
        'field_type' => ['text'],
        'field_required' => ['0' => '1'],
        'field_default_value' => [''],
        'field_placeholder' => ['Enter a title'],
    ], [], [], []);

    $response = $controller->store($request);
    $saved = $contentTypes->saved;

    expect($response->status())->toBe(302)
        ->and($response->header('Location'))->toBe('/admin/content-types')
        ->and($saved)->toBeInstanceOf(ContentType::class)
        ->and($saved?->fields())->toHaveCount(1)
        ->and($saved?->fields()[0]->settings())->toBe(['placeholder' => 'Enter a title']);
});

final class InMemoryContentTypeRepository implements ContentTypeRepositoryInterface
{
    public ?ContentType $saved = null;

    public function save(ContentType $contentType): ContentType
    {
        $this->saved = $contentType;

        return $contentType;
    }

    public function findByName(string $name): ?ContentType
    {
        if ($this->saved !== null && $this->saved->name() === $name) {
            return $this->saved;
        }

        return null;
    }

    public function findAll(): array
    {
        return $this->saved === null ? [] : [$this->saved];
    }

    public function getAllowedCategoryGroups(ContentType $type): array
    {
        return [];
    }

    public function attachCategoryGroup(ContentType $type, CategoryGroup $group): void
    {
    }

    public function detachCategoryGroup(ContentType $type, CategoryGroup $group): void
    {
    }

    public function remove(ContentType $contentType): void
    {
    }
}

final class InMemoryCategoryGroupRepository implements CategoryGroupRepositoryInterface
{
    public function save(CategoryGroup $group): CategoryGroup
    {
        return $group;
    }

    public function findById(int $id): ?CategoryGroup
    {
        return null;
    }

    public function findBySlug(string $slug): ?CategoryGroup
    {
        return null;
    }

    public function findAllGroups(): array
    {
        return [];
    }

    public function remove(CategoryGroup $group): void
    {
    }

    public function isInUse(CategoryGroup $group): bool
    {
        return false;
    }
}

final class NullContentRelationshipRepository implements ContentRelationshipRepositoryInterface
{
    public function findRelationshipRules(): array
    {
        return [];
    }

    public function findRelationshipRulesForContentType(ContentType $type): array
    {
        return [];
    }

    public function findOutgoingRelationships(ContentItem $item): array
    {
        return [];
    }

    public function findIncomingRelationships(ContentItem $item): array
    {
        return [];
    }

    public function findByType(ContentItem $item, string $relationType): array
    {
        return [];
    }

    public function attach(ContentItem $from, ContentItem $to, string $relationType, int $sortOrder = 0): void
    {
    }

    public function detach(ContentItem $from, ContentItem $to, string $relationType): void
    {
    }

    public function allowRelationship(ContentType $from, ContentType $to, string $relationType): void
    {
    }

    public function isRelationshipAllowed(ContentType $from, ContentType $to, string $relationType): bool
    {
        return true;
    }

    public function removeRelationshipRule(ContentType $from, ContentType $to, string $relationType): void
    {
    }
}
