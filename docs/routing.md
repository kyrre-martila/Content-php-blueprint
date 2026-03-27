# Routing

Routing is currently explicit and centralized in `src/Http/Kernel.php`.

## Public routes (current)

System routes:

- `GET /` → home controller.
- `GET /health` → health controller.
- `GET /install` and `POST /install` → installer (only while installation is required/not complete).
- `GET /search` → search controller rendered through `templates/system/search.php`.

Content routes:

- `GET /{slug}` → published content page (enabled when content repositories are available).

Not-found rendering for unresolved/unpublished content routes is handled by `templates/system/404.php` via `TemplateResolver::resolveNotFound()`.


## Authentication and admin routes (current)

- `GET /admin/login`
- `POST /admin/login`
- `POST /admin/logout`
- `GET /admin`

Content admin (when content repositories are available):

- `GET /admin/content`
- `GET /admin/content/create`
- `POST /admin/content/create`
- `GET /admin/content/{id}/edit`
- `POST /admin/content/{id}/edit`

Editor Mode:

- `POST /admin/editor-mode/enable`
- `POST /admin/editor-mode/disable`
- `POST /admin/editor-mode/update`

Dev Mode:

- `POST /admin/dev-mode/enable`
- `POST /admin/dev-mode/disable`
- `GET /admin/dev-mode`
- `GET /admin/dev-mode/edit`
- `POST /admin/dev-mode/edit`

## Middleware behavior (current)

The kernel wraps route handlers with callable middleware.

- CSRF middleware is applied to admin forms and state-changing routes.
- Auth middleware is required for protected admin actions.
- Install redirect checks run before router dispatch for setup-dependent admin paths.

## Planned direction (not yet implemented)

- Additional module routes should follow the same explicit registration style in `Kernel`.
- No runtime dynamic route registration or database-defined routes are planned.

## Template routing notes (v1)

- Content routes use a universal template (`templates/index.php`).
- System routes use explicit templates under `templates/system/*.php`.
- `404` resolves to `templates/system/404.php`.
- `search` resolves to `templates/system/search.php`.
- No WordPress-style slug/content-type template hierarchy is used in v1.
