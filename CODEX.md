# CODEX.md

Practical rules for AI coding agents working in this repository.

1. Read `DEVELOPMENT_PLAN.md` first.
2. Read relevant files in `docs/` before large or architectural changes.
3. Keep layered boundaries explicit (`Domain` → `Application` → `Infrastructure` → `Http/Admin`).
4. Prefer explicit classes and constructor wiring.
5. Do not add framework magic, hidden service locators, or implicit runtime behavior.
6. Keep `TemplateRenderer` lightweight; delegate cross-cutting concerns to dedicated services.
7. Keep `ApplicationFactory` from becoming a mega-bootstrap again.
8. Keep OCF export content-only.
9. Keep composition snapshot export blueprint-specific.
10. Keep Editor Mode content-safe.
11. Keep Dev Mode presentation-safe.
12. Prefer extending patterns over ad hoc template markup.
13. Update docs whenever architecture or operating behavior changes.
