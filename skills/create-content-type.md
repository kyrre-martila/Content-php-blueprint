# Skill: create-content-type

## Purpose
Add or extend a content type in a way that stays consistent with current repositories, admin forms, and template resolution.

## When to use
- New machine-readable content type is needed (e.g., `news`, `team`).
- Existing type label/template mapping must be updated.

## Architectural rules
- Keep schema changes in migrations; do not hardcode mutable schema in controllers.
- Keep orchestration in Application layer and persistence in Infrastructure repositories.
- Keep templates deterministic and render-only.

## File placement expectations
- Migration: `database/migrations/*`
- Domain/Application/Infrastructure updates under existing layer folders.
- Templates: `templates/pages/*` (or shared template fallbacks).
- Docs updates: `docs/content-model.md`, `docs/routing.md` when behavior changes.

## Implementation checklist
- [ ] Add/adjust migration or seed path for `content_types`.
- [ ] Ensure repository mapping and validation support new type.
- [ ] Ensure template resolution path exists.
- [ ] Update admin flows/tests if content type selection behavior changes.
- [ ] Update docs with current behavior.
