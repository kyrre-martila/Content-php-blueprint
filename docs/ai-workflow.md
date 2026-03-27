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


## Future sync model (documented direction)

This section defines target sync behavior for future implementation. It is architectural direction only.

### Dev Mode edits (presentation/source layer)

Dev Mode updates should eventually support Git-oriented repository sync patterns, for example:

- branch creation for isolated change sets
- patch export for review workflows
- PR-oriented handoff and merge workflows

These updates belong to source-managed presentation/runtime artifacts.

### Editor/content edits (runtime content layer)

Editor Mode and content-admin updates should eventually support content snapshot/export workflows, for example:

- content snapshot exports for transfer/backup
- OCF exports for portable structured content
- composition snapshot exports for blueprint-specific page assembly

These updates should not be modeled as direct source-code sync by default.

### AI context triad for site reasoning

AI should reason from three coordinated contexts:

1. repository files (code, templates, patterns, docs)
2. OCF content export (portable content semantics)
3. composition snapshot export (blueprint page assembly)

All three are required to reason accurately without collapsing content, composition, and source code into one layer.

## Non-goal for this phase

No in-app model invocation, prompt orchestration service, queue worker, or external AI API integration is introduced here.
