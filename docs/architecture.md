# Architecture

Content PHP Blueprint uses explicit layered architecture:

- `Domain`: entities, value objects, invariants.
- `Application`: use cases and orchestration DTOs.
- `Infrastructure`: MySQL repositories, view rendering, env/config, error/logging adapters.
- `Http`: request/response primitives, router, middleware, controllers.
- `Admin`: authenticated editor-facing controllers and form workflows.
- `templates/`: deterministic rendering only, no business logic.

## Runtime flow

`public/index.php` bootstraps config and infrastructure, then delegates to `App\Http\Kernel`.

`Kernel` wires route handlers with middleware, then `Router` dispatches deterministic path patterns to thin controllers.

## Hardening baseline (v0.1)

- Centralized exception/fatal error handling via `Infrastructure\Error\ErrorHandler`.
- File-based logging in `storage/logs/*.log` using `Infrastructure\Logging\Logger`.
- CSRF middleware for admin POST routes.
- Static analysis at PHPStan max level with committed baseline.
