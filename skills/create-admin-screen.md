# Skill: create-admin-screen

## Purpose
Create a safe admin screen without violating layering or security boundaries.

## When to use
- Add a new authenticated admin page/form/workflow.

## Architectural rules
- Controller should stay thin.
- Put business logic in Application/Domain, not template/controller.
- Protect POST routes with CSRF and auth middleware wrappers.

## File placement expectations
- Controller: `src/Admin/Controller/*Controller.php`
- Route wiring: `src/Http/Kernel.php`
- Template: `templates/admin/...`
- Optional supporting assets only if required.

## Implementation checklist
- [ ] Add controller action(s) with typed request handling.
- [ ] Register GET/POST routes explicitly in `Kernel`.
- [ ] Apply CSRF/auth wrappers for protected actions.
- [ ] Render escaped template output.
- [ ] Add tests and docs updates if behavior is architectural.
