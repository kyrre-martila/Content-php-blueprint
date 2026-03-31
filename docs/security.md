# Security deployment notes: trusted proxies and client IP resolution

This document covers **current deployment requirements** for trusted proxy configuration.

## Current implementation

### Configuration source

Set trusted proxy addresses via `.env`:

```dotenv
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
```

Runtime mapping:

- `.env` `TRUSTED_PROXIES`
- `config/security.php` -> `security.trusted_proxies`

### Resolution behavior

#### If `TRUSTED_PROXIES` is empty

- client IP = `REMOTE_ADDR`
- `X-Forwarded-For` is ignored

#### If `TRUSTED_PROXIES` is configured

- runtime only trusts `X-Forwarded-For` when immediate `REMOTE_ADDR` is inside configured trusted proxies
- otherwise runtime falls back to `REMOTE_ADDR`

### Why this matters

Login rate limiting uses resolved client IP.

Misconfigured proxy trust can cause unrelated users to share one effective IP identity, producing false lockouts and weaker abuse detection.

---

## Deployment checklist (current requirement)

Use this for nginx reverse proxies, managed load balancers, Cloudflare, and Cloudflare Tunnel:

- [ ] `TRUSTED_PROXIES` contains only actual trusted upstream hops that directly connect to the app.
- [ ] Proxy chain forwards `X-Forwarded-For` correctly.
- [ ] Deployment runbook includes a step to review/update trusted proxies after infra/network changes.
- [ ] Login/rate-limit behavior is validated from multiple real client IPs.

---

## Future roadmap (not implemented)

Potential future security-hardening additions (not current behavior):

- environment-specific trusted-proxy validation tooling
- startup diagnostics for suspicious proxy-header chains
- admin health warnings for likely proxy misconfiguration
