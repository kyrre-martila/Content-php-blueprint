# Skill: create-template

## Goal
Add deterministic rendering template(s).

## Workflow
1. Create file under `templates/pages` or `templates/components`.
2. Keep template free of business logic and database calls.
3. Escape all interpolated values with renderer-provided escaping helper.
4. Wire mapping through content type defaults or resolver fallback.
5. Add/adjust render-path tests where applicable.
