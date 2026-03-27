# Skill: add-field

## Purpose
Introduce a new content field safely across schema, domain/application contracts, admin UI, and persistence.

## When to use
- A content attribute must be stored and edited beyond current fields.

## Architectural rules
- Schema evolution must be explicit via migration.
- Keep validation and mapping in Application/Domain, not templates.
- Preserve editor-safe and dev-mode-safe boundaries.

## File placement expectations
- Migration: `database/migrations/*`
- DTO/use-case/domain updates in `src/Application` + `src/Domain`
- Repository updates in `src/Infrastructure/Content`
- Admin forms in `templates/admin/content/*`

## Implementation checklist
- [ ] Add migration and rollback where practical.
- [ ] Update DTOs/use cases/validation.
- [ ] Persist + hydrate field in repositories.
- [ ] Expose/edit field in admin UI if intended.
- [ ] Add tests and update `docs/content-model.md`.
