# Skill: create-template

## Purpose
Add or modify deterministic server-rendered templates.

## When to use
- New page template is required.
- Existing rendering structure needs safe extension.

## Architectural rules
- No business logic, DB calls, or service lookups in templates.
- Escape output through provided helpers.
- Prefer pattern rendering for reusable sections.

## File placement expectations
- Page templates: `templates/pages/*.php`
- Layouts: `templates/layouts/*.php`
- Generic fallback: `templates/default.php`

## Implementation checklist
- [ ] Create/modify template file in correct location.
- [ ] Keep logic minimal and deterministic.
- [ ] Use `$e` and renderer helpers for output safety.
- [ ] Confirm resolver path still deterministic.
- [ ] Update docs if resolution rules changed.
