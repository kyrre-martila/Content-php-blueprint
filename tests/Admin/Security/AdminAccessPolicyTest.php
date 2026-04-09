<?php

declare(strict_types=1);

use App\Admin\Security\AdminAccessPolicy;
use App\Domain\Auth\Role;

it('allows editor role on editor-safe admin paths', function (string $path): void {
    $policy = new AdminAccessPolicy();

    expect($policy->canAccessPath(Role::editor(), $path))->toBeTrue();
})->with([
    '/admin',
    '/admin/content',
    '/admin/content/create',
    '/admin/content/5/edit',
    '/admin/files',
    '/admin/files/upload',
    '/admin/files/10/edit',
]);

it('forbids editor role on restricted admin paths', function (string $path): void {
    $policy = new AdminAccessPolicy();

    expect($policy->canAccessPath(Role::editor(), $path))->toBeFalse();
})->with([
    '/admin/content-types',
    '/admin/content-types/create',
    '/admin/categories',
    '/admin/relationships',
    '/admin/templates',
    '/admin/system-templates',
    '/admin/dev-mode',
    '/admin/patterns',
]);

it('keeps admin and superadmin access unchanged for restricted routes', function (): void {
    $policy = new AdminAccessPolicy();
    $restrictedRoutes = [
        '/admin/content-types',
        '/admin/categories',
        '/admin/relationships',
        '/admin/templates',
        '/admin/system-templates',
        '/admin/dev-mode',
    ];

    foreach ($restrictedRoutes as $route) {
        expect($policy->canAccessPath(Role::admin(), $route))->toBeTrue()
            ->and($policy->canAccessPath(Role::superadmin(), $route))->toBeTrue();
    }
});
