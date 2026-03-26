# Skill: create-content-type

## Goal
Add a new content type deterministically.

## Workflow
1. Add/verify migration updates for `content_types` seed data (if needed).
2. Register type through repository/application layer (avoid hardcoded template logic in controllers).
3. Ensure template mapping exists under `templates/pages`.
4. Add/extend tests for repository retrieval and routing/rendering.
5. Update `docs/content-model.md` and `docs/routing.md` if behavior changes.
