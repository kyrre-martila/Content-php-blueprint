# Skill: write-migration

## Goal
Create a production-safe database migration.

## Workflow
1. Generate timestamped migration via Phinx.
2. Implement forward schema change with deterministic SQL.
3. Implement rollback path when practical.
4. Validate with `composer migrate:status` and a local run.
5. Document schema impact in `docs/content-model.md`.
