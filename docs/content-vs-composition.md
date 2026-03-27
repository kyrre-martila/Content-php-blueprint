# Content vs composition

This document formalizes the boundary between **portable content**, **blueprint-specific composition**, and **repository-managed presentation code**.

## 1) Content (portable/interoperable)

Content is the semantic information that should remain useful across platforms and rendering systems.

Examples:

- titles, summaries, body text
- structured field values
- taxonomies or semantic relationships
- publish metadata and content-state attributes

In Content PHP Blueprint, this layer is intended to be exportable through **Open Content Format (OCF)**.

Key properties:

- portable
- interoperable
- vendor-neutral
- suitable for content snapshots and transfer workflows

## 2) Composition (blueprint-specific assembly)

Composition describes how a given blueprint assembles page output from reusable pieces.

Examples:

- ordered pattern lists per page
- pattern field grouping tied to page routes/slugs
- system route composition (such as search)
- template association used by this blueprint runtime

Key properties:

- blueprint-specific
- tied to this project's rendering model
- useful for AI/tooling reconstruction of page assembly
- not portable in the same way as content

Composition data should therefore be represented in a **separate composition snapshot format**, not inside OCF.

## 3) Code/presentation (repository-managed source)

Presentation/runtime implementation remains source-managed in Git.

Includes:

- templates
- patterns (`pattern.json` + `pattern.php`)
- CSS
- JS
- application/runtime code
- docs and skills

This layer is versioned as source code and follows repository workflows.

## Why these concerns must remain separate

Keeping content, composition, and code separate prevents architectural drift and preserves each layer's strengths:

- OCF stays clean and portable because it does not absorb template/layout/runtime details.
- Composition remains explicit for blueprint-specific page assembly and AI context.
- Source code remains version-controlled and reviewable through standard repository practices.
- Editor-facing content operations can be exported as content snapshots without being confused with source-code changes.
- Dev-facing presentation work can evolve in Git without polluting portable content artifacts.
