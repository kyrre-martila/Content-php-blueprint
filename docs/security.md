# Security deployment notes: trusted proxies and client IP resolution

This project uses `security.trusted_proxies` (configured via `TRUSTED_PROXIES` in `.env`) to decide when proxy headers are trusted.

## Configuration key

Set trusted proxy addresses as a comma-separated list of individual IPs and/or CIDR ranges:

```dotenv
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
```

Runtime mapping:

- `.env` `TRUSTED_PROXIES` -> `config/security.php` -> `security.trusted_proxies`

## Behavior

### When `TRUSTED_PROXIES` is empty

- Client IP resolves from `REMOTE_ADDR`.
- `X-Forwarded-For` is ignored.

This is safe for direct deployments where the client connects straight to PHP/web server.

### When `TRUSTED_PROXIES` is configured

- `X-Forwarded-For` is only used when `REMOTE_ADDR` matches a trusted proxy IP/CIDR.
- If `REMOTE_ADDR` is not trusted, resolver falls back to `REMOTE_ADDR`.

This protects against forged `X-Forwarded-For` headers from untrusted clients.

## Cloudflare deployment example

If traffic flows through Cloudflare (including Cloudflare Tunnel), your app usually sees a proxy address unless your edge/proxy chain is configured correctly.

Recommended approach:

1. Ensure your nginx/load balancer forwards `X-Forwarded-For` correctly.
2. Add only your *actual upstream proxy addresses/ranges* to `TRUSTED_PROXIES`.
3. Keep the list current whenever infrastructure changes.

### Client IP resolution in Cloudflare-like topologies

- Correct config: application resolves the real visitor IP from `X-Forwarded-For` (trusted hop), so auth and rate limiting behave per user.
- Incorrect config: application resolves the proxy/tunnel/load balancer IP, making many users appear as one source.

### Rate limiter dependency

Login rate limiting depends on resolved client IP. If proxy trust is wrong, users share one effective IP identity.

> ⚠️ Warning: incorrect trusted proxy configuration can cause a shared rate-limit bucket across unrelated users, resulting in noisy lockouts and reduced abuse-detection accuracy.

## Reverse proxy and load balancer checklist

Use this checklist for nginx reverse proxy, managed load balancers, and Cloudflare Tunnel:

- [ ] `TRUSTED_PROXIES` contains only trusted hops that directly connect to your app.
- [ ] Proxy chain passes `X-Forwarded-For` without stripping or malformed rewrites.
- [ ] A deploy/runbook step exists to update `TRUSTED_PROXIES` after network changes.
- [ ] Login attempts are tested from different client IPs to confirm independent rate-limit buckets.
