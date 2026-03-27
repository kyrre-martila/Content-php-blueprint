# Coding standards

## Baseline requirements (current)

- PHP 8.3+.
- `declare(strict_types=1);` in PHP files.
- PSR-12 style.
- Constructor injection and explicit dependencies.
- Thin controllers; domain/application logic outside templates/controllers.

## Layering rules

- Domain layer must not depend on infrastructure concerns.
- Application layer orchestrates use cases, not HTTP/framework details.
- Infrastructure implements interfaces/adapters.
- Templates are render-only (no business rules or DB calls).

## Security rules

- Escape output in templates (`$e` helper).
- Use parameterized DB queries in repositories.
- Require CSRF checks for admin state changes.
- Keep editor-safe and dev-safe boundaries explicit.

## Documentation and workflow rules

- Update `docs/` when architecture behavior changes.
- Prefer extending existing subsystems (patterns, resolver, admin flows) instead of parallel abstractions.
- Keep new modules small and deterministic for repeatable AI-assisted changes.

## Quality checks

- `composer test`
- `composer analyse`

If environment limits prevent a check, record that explicitly in the change summary.
