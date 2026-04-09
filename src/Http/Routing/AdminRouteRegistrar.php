<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\CategoryAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\ContentTypeAdminController;
use App\Admin\Controller\FileAdminController;
use App\Admin\Controller\TemplateAdminController;
use App\Admin\Controller\PatternController;
use App\Admin\Controller\RelationshipAdminController;
use App\Admin\Security\AdminAccessPolicy;
use App\Domain\Auth\Role;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\MiddlewareStackBuilder;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Middleware\RequireRoleMiddleware;

final class AdminRouteRegistrar
{
    public function __construct(
        private readonly DashboardController $dashboardController,
        private readonly PatternController $patternController,
        private readonly ?ContentAdminController $contentAdminController,
        private readonly ?TemplateAdminController $templateAdminController,
        private readonly ?FileAdminController $fileAdminController,
        private readonly ?ContentTypeAdminController $contentTypeAdminController,
        private readonly ?CategoryAdminController $categoryAdminController,
        private readonly ?RelationshipAdminController $relationshipAdminController,
        private readonly AdminAccessPolicy $accessPolicy,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
        private readonly RequireRoleMiddleware $requireEditorOrAdminRole,
        private readonly RequireRoleMiddleware $requireAdminRole,
        private readonly MiddlewareStackBuilder $middlewareStackBuilder,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $routeRegistry->get('/admin', $this->middlewareStackBuilder->wrap([
            $this->dashboardController,
            'index',
        ], $this->middlewareForPath('/admin')));

        $routeRegistry->get('/admin/patterns', $this->middlewareStackBuilder->wrap([
            $this->patternController,
            'index',
        ], $this->middlewareForPath('/admin/patterns')));

        if ($this->templateAdminController !== null) {
            $routeRegistry->get('/admin/templates', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'index',
            ], $this->middlewareForPath('/admin/templates')));
            $routeRegistry->get('/admin/system-templates', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'systemIndex',
            ], $this->middlewareForPath('/admin/system-templates')));
            $routeRegistry->get('/admin/templates/edit', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'edit',
            ], $this->middlewareForPath('/admin/templates/edit')));
            $routeRegistry->post('/admin/templates/edit', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'update',
            ], $this->middlewareForPath('/admin/templates/edit')));
        }

        if ($this->fileAdminController !== null) {
            $routeRegistry->get('/admin/files', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'index',
            ], $this->middlewareForPath('/admin/files')));
            $routeRegistry->get('/admin/files/upload', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'upload',
            ], $this->middlewareForPath('/admin/files/upload')));
            $routeRegistry->post('/admin/files/upload', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'storeUpload',
            ], $this->middlewareForPath('/admin/files/upload')));
            $routeRegistry->get('/admin/files/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'edit',
            ], $this->middlewareForPath('/admin/files/{id}/edit')));
            $routeRegistry->post('/admin/files/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'update',
            ], $this->middlewareForPath('/admin/files/{id}/edit')));
            $routeRegistry->delete('/admin/files/{id}', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'destroy',
            ], $this->middlewareForPath('/admin/files/{id}')));
            $routeRegistry->post('/admin/files/{id}', $this->middlewareStackBuilder->wrap([
                $this->fileAdminController,
                'destroy',
            ], $this->middlewareForPath('/admin/files/{id}')));
        }

        if ($this->contentTypeAdminController !== null) {
            $routeRegistry->get('/admin/content-types', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'index',
            ], $this->middlewareForPath('/admin/content-types')));
            $routeRegistry->get('/admin/content-types/create', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'create',
            ], $this->middlewareForPath('/admin/content-types/create')));
            $routeRegistry->post('/admin/content-types/create', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'store',
            ], $this->middlewareForPath('/admin/content-types/create')));
            $routeRegistry->get('/admin/content-types/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'edit',
            ], $this->middlewareForPath('/admin/content-types/{id}/edit')));
            $routeRegistry->post('/admin/content-types/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'update',
            ], $this->middlewareForPath('/admin/content-types/{id}/edit')));
            $routeRegistry->delete('/admin/content-types/{slug}', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'destroy',
            ], $this->middlewareForPath('/admin/content-types/{slug}')));
            $routeRegistry->post('/admin/content-types/{slug}', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'destroy',
            ], $this->middlewareForPath('/admin/content-types/{slug}')));
        }

        if ($this->categoryAdminController !== null) {
            $routeRegistry->get('/admin/categories', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'index',
            ], $this->middlewareForPath('/admin/categories')));
            $routeRegistry->post('/admin/categories/groups/create', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'storeGroup',
            ], $this->middlewareForPath('/admin/categories/groups/create')));
            $routeRegistry->post('/admin/categories/groups/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'updateGroup',
            ], $this->middlewareForPath('/admin/categories/groups/{id}/edit')));
            $routeRegistry->post('/admin/categories/groups/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyGroup',
            ], $this->middlewareForPath('/admin/categories/groups/{id}')));
            $routeRegistry->delete('/admin/categories/groups/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyGroup',
            ], $this->middlewareForPath('/admin/categories/groups/{id}')));
            $routeRegistry->post('/admin/categories/create', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'storeCategory',
            ], $this->middlewareForPath('/admin/categories/create')));
            $routeRegistry->post('/admin/categories/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'updateCategory',
            ], $this->middlewareForPath('/admin/categories/{id}/edit')));
            $routeRegistry->post('/admin/categories/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyCategory',
            ], $this->middlewareForPath('/admin/categories/{id}')));
            $routeRegistry->delete('/admin/categories/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyCategory',
            ], $this->middlewareForPath('/admin/categories/{id}')));
        }

        if ($this->relationshipAdminController !== null) {
            $routeRegistry->get('/admin/relationships', $this->middlewareStackBuilder->wrap([
                $this->relationshipAdminController,
                'index',
            ], $this->middlewareForPath('/admin/relationships')));
            $routeRegistry->post('/admin/relationships/rules/create', $this->middlewareStackBuilder->wrap([
                $this->relationshipAdminController,
                'storeRule',
            ], $this->middlewareForPath('/admin/relationships/rules/create')));
            $routeRegistry->post('/admin/relationships/rules/{fromType}/{toType}/{relationType}', $this->middlewareStackBuilder->wrap([
                $this->relationshipAdminController,
                'destroyRule',
            ], $this->middlewareForPath('/admin/relationships/rules/{fromType}/{toType}/{relationType}')));
            $routeRegistry->delete('/admin/relationships/rules/{fromType}/{toType}/{relationType}', $this->middlewareStackBuilder->wrap([
                $this->relationshipAdminController,
                'destroyRule',
            ], $this->middlewareForPath('/admin/relationships/rules/{fromType}/{toType}/{relationType}')));
        }

        if ($this->contentAdminController === null) {
            return;
        }

        $routeRegistry->get('/admin/content', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'index',
        ], $this->middlewareForPath('/admin/content')));
        $routeRegistry->get('/admin/content/create', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'create',
        ], $this->middlewareForPath('/admin/content/create')));
        $routeRegistry->post('/admin/content/create', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'store',
        ], $this->middlewareForPath('/admin/content/create')));
        $routeRegistry->get('/admin/content/{id}/edit', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'edit',
        ], $this->middlewareForPath('/admin/content/{id}/edit')));
        $routeRegistry->post('/admin/content/{id}/edit', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'update',
        ], $this->middlewareForPath('/admin/content/{id}/edit')));
        $routeRegistry->delete('/admin/content/{id}', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'destroy',
        ], $this->middlewareForPath('/admin/content/{id}')));
        $routeRegistry->post('/admin/content/{id}', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'destroy',
        ], $this->middlewareForPath('/admin/content/{id}')));
    }

    /**
     * @return list<object>
     */
    private function middlewareForPath(string $path): array
    {
        $roles = $this->accessPolicy->allowedRolesForPath($path);
        $containsEditor = false;

        foreach ($roles as $role) {
            if ($role->equals(Role::editor())) {
                $containsEditor = true;
                break;
            }
        }

        return $containsEditor
            ? [$this->csrf, $this->requireAuth, $this->requireEditorOrAdminRole]
            : [$this->csrf, $this->requireAuth, $this->requireAdminRole];
    }
}
