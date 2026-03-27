# Skill: write-migration

## Purpose
Create a deterministic, production-safe schema migration.

## When to use
- Any persistent schema/data-shape change is needed.

## Architectural rules
- Migration is the source of truth for schema evolution.
- Avoid hidden runtime schema creation in app code.
- Keep SQL deterministic and reversible where practical.

## File placement expectations
- New migration in `database/migrations/` using project timestamp naming style.

## Implementation checklist
- [ ] Create migration file with clear intent.
- [ ] Implement `up` change deterministically.
- [ ] Implement rollback (`down`) when feasible.
- [ ] Validate migration status/run locally.
- [ ] Update docs (`docs/content-model.md`) with current schema behavior.
