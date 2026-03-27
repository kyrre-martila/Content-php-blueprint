# Architecture

Content PHP Blueprint currently runs as a framework-light, layered PHP application with explicit boundaries.

## Current implementation

### Layer map

- `src/Domain/`: entities/value objects and invariants (`ContentItem`, `ContentType`, `Slug`, roles, statuses).
- `src/Application/`: use-case services and DTOs (`CreateContentItem`, `UpdateContentItem`, `ListContentItems`, `LoginUser`).
- `src/Infrastructure/`: adapters (MySQL repositories, config/env loading, logging, error handling, template/pattern rendering, editor/dev mode helpers).
- `src/Http/`: request/response primitives, router, middleware, public controllers, and `Kernel` wiring.
- `src/Admin/`: authenticated admin controllers for auth, dashboard, content CRUD, Editor Mode updates, and Dev Mode file editing.
- `templates/`: deterministic server-rendered PHP templates.
  - v1 uses `templates/index.php` as the universal content renderer.
  - v1 system routes render through `templates/system/*.php` (`404.php`, `search.php`).
- `patterns/`: reusable presentation blocks (`pattern.json` metadata + `pattern.php` view).

### Runtime composition

- Entry point is `public/index.php` (or root `index.php` compatibility wrapper).
- `App\Http\Kernel` composes infrastructure and route definitions.
- `Router` dispatches to thin controllers.
- State-changing admin routes are wrapped with CSRF and auth checks.

### Data and rendering flow

1. Controller resolves use case/repository data.
2. `TemplateResolver` deterministically maps content routes to `templates/index.php` and system routes to `templates/system/*.php`, with safe fallback to `templates/system/404.php` for missing system templates.
3. `TemplateRenderer` renders template and optional layout.
4. Templates can render patterns through `PatternRenderer`.
5. Pattern rendering is registry-based and field-filtered (only declared scalar fields passed through).

## Guardrails currently enforced

- Strict typing (`declare(strict_types=1);`) and constructor injection.
- Centralized error handling and file-based logging.
- CSRF protection for admin writes.
- Role-gated Editor Mode (content-safe inline edits only).
- Role-gated Dev Mode with constrained filesystem roots and extensions.

## Planned direction (not yet implemented)

- Field-value infrastructure beyond current title + pattern block editing.
- Broader content type tooling and richer admin scaffolding workflows.
- Site generation driven by a blueprint manifest and AI playbooks (repository-level workflow, not runtime AI calls).
