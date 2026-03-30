<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\CategoryRepositoryInterface;
use App\Domain\Content\Slug;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplateRenderer;
use DateTimeImmutable;

final class CategoryAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly CategoryGroupRepositoryInterface $categoryGroups,
        private readonly CategoryRepositoryInterface $categories,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $groups = $this->categoryGroups->findAllGroups();
        $selectedGroup = $this->resolveSelectedGroup($request, $groups);
        $selectedGroupId = $selectedGroup?->id();

        $categories = $selectedGroup !== null
            ? $this->categories->findCategoriesByGroup($selectedGroup)
            : [];

        $categoryRows = $this->flattenCategoryTree($categories);

        $html = $this->templateRenderer->renderTemplate(
            'admin/categories/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'groups' => $groups,
                'selectedGroup' => $selectedGroup,
                'selectedGroupId' => $selectedGroupId,
                'categories' => $categories,
                'categoryRows' => $categoryRows,
                'editingGroupId' => $this->queryInt($request, 'edit_group'),
                'editingCategoryId' => $this->queryInt($request, 'edit_category'),
                'groupSuccess' => $this->session->pullFlash('category_group_success'),
                'groupError' => $this->session->pullFlash('category_group_error'),
                'categorySuccess' => $this->session->pullFlash('category_success'),
                'categoryError' => $this->session->pullFlash('category_error'),
            ]
        );

        return Response::html($html);
    }

    public function storeGroup(Request $request): Response
    {
        $post = $request->postParams();
        $name = is_string($post['name'] ?? null) ? trim($post['name']) : '';
        $slug = is_string($post['slug'] ?? null) ? trim($post['slug']) : '';
        $description = is_string($post['description'] ?? null) ? trim($post['description']) : '';

        if ($name === '' || $slug === '') {
            $this->session->flash('category_group_error', 'Category group name and slug are required.');

            return Response::redirect('/admin/categories');
        }

        if ($this->categoryGroups->findBySlug($slug) !== null) {
            $this->session->flash('category_group_error', 'A category group with this slug already exists.');

            return Response::redirect('/admin/categories');
        }

        try {
            $this->categoryGroups->save(new CategoryGroup(
                id: null,
                name: $name,
                slug: Slug::fromString($slug),
                description: $description === '' ? null : $description,
                createdAt: new DateTimeImmutable(),
                updatedAt: new DateTimeImmutable(),
            ));
        } catch (\Throwable) {
            $this->session->flash('category_group_error', 'Unable to create category group.');

            return Response::redirect('/admin/categories');
        }

        $this->session->flash('category_group_success', sprintf('Category group "%s" created.', $name));

        return Response::redirect('/admin/categories');
    }

    public function updateGroup(Request $request): Response
    {
        $groupId = $this->routeInt($request, 'id');

        if ($groupId === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $group = $this->categoryGroups->findById($groupId);

        if ($group === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $post = $request->postParams();
        $name = is_string($post['name'] ?? null) ? trim($post['name']) : '';
        $slug = is_string($post['slug'] ?? null) ? trim($post['slug']) : '';
        $description = is_string($post['description'] ?? null) ? trim($post['description']) : '';

        if ($name === '' || $slug === '') {
            $this->session->flash('category_group_error', 'Category group name and slug are required.');

            return Response::redirect('/admin/categories?group=' . $groupId . '&edit_group=' . $groupId);
        }

        $existing = $this->categoryGroups->findBySlug($slug);
        if ($existing !== null && $existing->id() !== $groupId) {
            $this->session->flash('category_group_error', 'A category group with this slug already exists.');

            return Response::redirect('/admin/categories?group=' . $groupId . '&edit_group=' . $groupId);
        }

        try {
            $this->categoryGroups->save(new CategoryGroup(
                id: $groupId,
                name: $name,
                slug: Slug::fromString($slug),
                description: $description === '' ? null : $description,
                createdAt: $group->createdAt(),
                updatedAt: new DateTimeImmutable(),
            ));
        } catch (\Throwable) {
            $this->session->flash('category_group_error', 'Unable to update category group.');

            return Response::redirect('/admin/categories?group=' . $groupId . '&edit_group=' . $groupId);
        }

        $this->session->flash('category_group_success', sprintf('Category group "%s" updated.', $name));

        return Response::redirect('/admin/categories?group=' . $groupId);
    }

    public function destroyGroup(Request $request): Response
    {
        if (!$this->isDeleteMethod($request) || !$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 400);
        }

        $groupId = $this->routeInt($request, 'id');

        if ($groupId === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $group = $this->categoryGroups->findById($groupId);

        if ($group === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        if ($this->categoryGroups->isInUse($group)) {
            $this->session->flash('category_group_error', 'Cannot delete this category group because it is in use by categories or content type mappings.');

            return Response::redirect('/admin/categories?group=' . $groupId);
        }

        $this->categoryGroups->remove($group);
        $this->session->flash('category_group_success', sprintf('Category group "%s" deleted.', $group->name()));

        return Response::redirect('/admin/categories');
    }

    public function storeCategory(Request $request): Response
    {
        $post = $request->postParams();
        $groupId = $this->postInt($post, 'group_id');

        if ($groupId === null) {
            $this->session->flash('category_error', 'Select a category group before creating categories.');

            return Response::redirect('/admin/categories');
        }

        $group = $this->categoryGroups->findById($groupId);

        if ($group === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $name = is_string($post['name'] ?? null) ? trim($post['name']) : '';
        $slug = is_string($post['slug'] ?? null) ? trim($post['slug']) : '';
        $description = is_string($post['description'] ?? null) ? trim($post['description']) : '';
        $parentId = $this->postInt($post, 'parent_id');
        $sortOrder = $this->postInt($post, 'sort_order') ?? 0;

        if ($name === '' || $slug === '') {
            $this->session->flash('category_error', 'Category name and slug are required.');

            return Response::redirect('/admin/categories?group=' . $groupId);
        }

        if ($this->isDuplicateSlugInGroup($group, $slug)) {
            $this->session->flash('category_error', 'A category with this slug already exists in the selected group.');

            return Response::redirect('/admin/categories?group=' . $groupId);
        }

        if ($parentId !== null && !$this->isParentInGroup($group, $parentId)) {
            $this->session->flash('category_error', 'Selected parent category is invalid for this group.');

            return Response::redirect('/admin/categories?group=' . $groupId);
        }

        try {
            $this->categories->save(new Category(
                id: null,
                groupId: $groupId,
                parentId: $parentId,
                name: $name,
                slug: Slug::fromString($slug),
                description: $description === '' ? null : $description,
                sortOrder: $sortOrder,
                createdAt: new DateTimeImmutable(),
                updatedAt: new DateTimeImmutable(),
            ));
        } catch (\Throwable) {
            $this->session->flash('category_error', 'Unable to create category.');

            return Response::redirect('/admin/categories?group=' . $groupId);
        }

        $this->session->flash('category_success', sprintf('Category "%s" created.', $name));

        return Response::redirect('/admin/categories?group=' . $groupId);
    }

    public function updateCategory(Request $request): Response
    {
        $categoryId = $this->routeInt($request, 'id');

        if ($categoryId === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $category = $this->categories->findById($categoryId);

        if ($category === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $group = $this->categoryGroups->findById($category->groupId());

        if ($group === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $post = $request->postParams();
        $name = is_string($post['name'] ?? null) ? trim($post['name']) : '';
        $slug = is_string($post['slug'] ?? null) ? trim($post['slug']) : '';
        $description = is_string($post['description'] ?? null) ? trim($post['description']) : '';
        $parentId = $this->postInt($post, 'parent_id');
        $sortOrder = $this->postInt($post, 'sort_order') ?? 0;

        if ($name === '' || $slug === '') {
            $this->session->flash('category_error', 'Category name and slug are required.');

            return Response::redirect('/admin/categories?group=' . $group->id() . '&edit_category=' . $categoryId);
        }

        if ($this->isDuplicateSlugInGroup($group, $slug, $categoryId)) {
            $this->session->flash('category_error', 'A category with this slug already exists in the selected group.');

            return Response::redirect('/admin/categories?group=' . $group->id() . '&edit_category=' . $categoryId);
        }

        if ($parentId !== null) {
            if ($parentId === $categoryId || !$this->isParentInGroup($group, $parentId) || $this->isDescendantOf($group, $parentId, $categoryId)) {
                $this->session->flash('category_error', 'Selected parent category is invalid.');

                return Response::redirect('/admin/categories?group=' . $group->id() . '&edit_category=' . $categoryId);
            }
        }

        try {
            $this->categories->save(new Category(
                id: $categoryId,
                groupId: $category->groupId(),
                parentId: $parentId,
                name: $name,
                slug: Slug::fromString($slug),
                description: $description === '' ? null : $description,
                sortOrder: $sortOrder,
                createdAt: $category->createdAt(),
                updatedAt: new DateTimeImmutable(),
            ));
        } catch (\Throwable) {
            $this->session->flash('category_error', 'Unable to update category.');

            return Response::redirect('/admin/categories?group=' . $group->id() . '&edit_category=' . $categoryId);
        }

        $this->session->flash('category_success', sprintf('Category "%s" updated.', $name));

        return Response::redirect('/admin/categories?group=' . $group->id());
    }

    public function destroyCategory(Request $request): Response
    {
        if (!$this->isDeleteMethod($request) || !$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 400);
        }

        $categoryId = $this->routeInt($request, 'id');

        if ($categoryId === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $category = $this->categories->findById($categoryId);

        if ($category === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        if ($this->categories->isAssignedToContentItems($category)) {
            $this->session->flash('category_error', 'Cannot delete category because it is assigned to content items.');

            return Response::redirect('/admin/categories?group=' . $category->groupId());
        }

        if ($this->categories->hasChildren($category)) {
            $this->session->flash('category_error', 'Cannot delete category while child categories exist. Reassign or delete children first.');

            return Response::redirect('/admin/categories?group=' . $category->groupId());
        }

        $this->categories->remove($category);
        $this->session->flash('category_success', sprintf('Category "%s" deleted.', $category->name()));

        return Response::redirect('/admin/categories?group=' . $category->groupId());
    }

    /**
     * @param list<CategoryGroup> $groups
     */
    private function resolveSelectedGroup(Request $request, array $groups): ?CategoryGroup
    {
        if ($groups === []) {
            return null;
        }

        $requestedGroup = $request->queryParams()['group'] ?? null;

        if (is_string($requestedGroup) && ctype_digit($requestedGroup)) {
            $groupId = (int) $requestedGroup;

            foreach ($groups as $group) {
                if ($group->id() === $groupId) {
                    return $group;
                }
            }
        }

        return $groups[0];
    }

    /**
     * @param list<Category> $categories
     * @return list<array{category: Category, depth: int}>
     */
    private function flattenCategoryTree(array $categories): array
    {
        $childrenMap = [];

        foreach ($categories as $category) {
            $parentId = $category->parentId() ?? 0;
            $childrenMap[$parentId] ??= [];
            $childrenMap[$parentId][] = $category;
        }

        $rows = [];
        $visited = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$rows, &$visited, $childrenMap): void {
            foreach ($childrenMap[$parentId] ?? [] as $category) {
                $categoryId = $category->id();

                if ($categoryId === null || isset($visited[$categoryId])) {
                    continue;
                }

                $visited[$categoryId] = true;
                $rows[] = ['category' => $category, 'depth' => $depth];
                $walk($categoryId, $depth + 1);
            }
        };

        $walk(0, 0);

        return $rows;
    }

    private function queryInt(Request $request, string $key): ?int
    {
        $value = $request->queryParams()[$key] ?? null;

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    private function routeInt(Request $request, string $key): ?int
    {
        $value = $request->attribute($key);

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    /** @param array<string, mixed> $post */
    private function postInt(array $post, string $key): ?int
    {
        $value = $post[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && preg_match('/^-?\d+$/', $value) === 1
            ? (int) $value
            : null;
    }

    private function isDuplicateSlugInGroup(CategoryGroup $group, string $slug, ?int $exceptCategoryId = null): bool
    {
        foreach ($this->categories->findCategoriesByGroup($group) as $existingCategory) {
            if ($existingCategory->slug()->value() !== $slug) {
                continue;
            }

            if ($exceptCategoryId !== null && $existingCategory->id() === $exceptCategoryId) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isParentInGroup(CategoryGroup $group, int $parentId): bool
    {
        foreach ($this->categories->findCategoriesByGroup($group) as $existingCategory) {
            if ($existingCategory->id() === $parentId) {
                return true;
            }
        }

        return false;
    }

    private function isDescendantOf(CategoryGroup $group, int $candidateParentId, int $categoryId): bool
    {
        $childrenByParent = [];

        foreach ($this->categories->findCategoriesByGroup($group) as $groupCategory) {
            $childrenByParent[$groupCategory->parentId() ?? 0][] = $groupCategory->id();
        }

        $stack = [$categoryId];

        while ($stack !== []) {
            $current = array_pop($stack);

            if (!is_int($current)) {
                continue;
            }

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                if (!is_int($childId)) {
                    continue;
                }

                if ($childId === $candidateParentId) {
                    return true;
                }

                $stack[] = $childId;
            }
        }

        return false;
    }

    private function isDeleteMethod(Request $request): bool
    {
        if ($request->method() === 'DELETE') {
            return true;
        }

        $methodOverride = $request->postParams()['_method'] ?? null;

        return is_string($methodOverride) && strtoupper($methodOverride) === 'DELETE';
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        $sessionToken = $this->session->get('_csrf_token');

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $submittedToken = $request->postParams()['_csrf_token'] ?? null;

        return is_string($submittedToken) && hash_equals($sessionToken, $submittedToken);
    }
}
