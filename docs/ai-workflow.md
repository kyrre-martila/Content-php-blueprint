# AI workflow

This repository is designed to be operated as an **AI-first blueprint**, without embedding runtime AI calls inside the app.

## Core principle

**AI builds the system. Humans refine it. Editors run it.**

## Intended workflow

1. Clone this blueprint repository.
2. Use AI to generate the initial site structure:
   - content types
   - templates
   - patterns
   - admin scaffolding where needed
3. Review/refine generated code and docs in Git.
4. Hand off day-to-day content operations to editors.
5. Use Dev Mode only for trusted presentation-layer adjustments.

## Responsibility boundaries

### AI-generated system structure

AI can generate/extend:

- `src/` modules that follow existing layers
- `templates/` page/layout templates
- `patterns/` blocks and metadata
- `docs/` and `skills/` operating documentation
- migration files for explicit schema changes

### Editor-managed content

Editors operate through admin UI + Editor Mode for safe inline text updates.

Editors do not manage:

- template source
- pattern implementation source
- routing and architecture code

### Developer-managed presentation

Developers or high-trust operators can use Dev Mode for constrained presentation updates only:

- templates
- patterns
- CSS/JS assets

App core logic and infrastructure remain outside Dev Mode scope.

## Repository operating context for AI

- `docs/` = architectural memory and implementation constraints.
- `skills/` = reusable execution playbooks.
- `blueprint.site.example.json` = example manifest contract for future generation runs.
- `CODEX.md` = concise repo-level instructions for coding agents.

## Non-goal for this phase

No in-app model invocation, prompt orchestration service, queue worker, or external AI API integration is introduced here.
