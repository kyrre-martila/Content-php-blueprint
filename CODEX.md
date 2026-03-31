# CODEX.md

Practical rules for AI coding agents working in this repository.

## Required reading order

1. `DEVELOPMENT_PLAN.md`
2. Relevant `docs/*.md` for the subsystem being changed
3. The actual implementation files in `src/`, `templates/`, `public/`, and scripts

## Operating rules (current implementation first)

1. Treat code as runtime truth; treat docs as the operating contract that must stay aligned with code.
2. Keep architectural boundaries explicit (`Domain` -> `Application` -> `Infrastructure` -> `Http/Admin`).
3. Keep template resolution deterministic; do not introduce implicit hierarchy chains.
4. Respect current public category routing: `GET /categories/{groupSlug}/{categorySlug}`.
5. Keep hierarchy, categories, and relationships as separate systems.
6. Keep `TemplateRenderer` lightweight and delegate cross-cutting logic to dedicated services.
7. Prevent `ApplicationFactory` from becoming a mega-bootstrap.
8. Keep OCF export content-only and composition snapshot blueprint-specific.
9. Keep Editor Mode content-safe and Dev Mode presentation-safe.
10. Preserve deployment/runtime boundaries: release artifacts are source delivery; `.env` and `storage/` are persistent runtime state.
11. Keep trusted-proxy behavior explicit and environment-specific (`TRUSTED_PROXIES`).
12. Update docs whenever architecture or operational behavior changes.

## Roadmap handling rule

When discussing planned work, label it clearly as **future roadmap** and do not describe it as current runtime behavior.
