# Routing

Routing is deterministic and declared in `App\Http\Kernel`.

## Public routes

- `GET /` home page
- `GET /health` health response
- `GET /{slug}` content detail fallback (when content repositories are available)

## Admin routes

- `GET /admin/login`
- `POST /admin/login` (CSRF protected)
- `POST /admin/logout` (auth + CSRF)
- `GET /admin` (auth)
- Content CRUD under `/admin/content*` (auth + CSRF for POST)

## Middleware order

For protected admin writes:
1. CSRF middleware validates `_csrf_token`.
2. Auth middleware verifies signed-in user.
3. Controller executes use case.

This order prevents unauthenticated and forged write attempts.
