# Skill: create-admin-screen

## Goal
Create a safe admin UI screen without breaking architecture.

## Workflow
1. Add controller in `src/Admin/Controller` with thin orchestration.
2. Register route in `Kernel` with auth middleware and CSRF middleware for POST.
3. Create template in `templates/admin/...` with escaped output.
4. Keep business logic in Application services.
5. Add HTTP/controller tests and documentation updates.
