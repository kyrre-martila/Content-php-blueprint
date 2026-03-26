# Coding standards

## Required

- `declare(strict_types=1);` in all PHP files.
- PSR-12 formatting and naming.
- Constructor injection over service locators.
- Thin controllers; business rules in Domain/Application.
- Explicit dependencies and typed arrays/docblocks.

## Security defaults

- Escape template output.
- Use prepared SQL statements.
- Protect state-changing admin requests with CSRF checks.
- Log operational failures to file channels.

## Quality gates

- Pest test suite must pass.
- PHPStan level `max` must pass.
- Documentation updates required for architectural changes.
