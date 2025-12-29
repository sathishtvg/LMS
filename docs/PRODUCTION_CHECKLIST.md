# Production Checklist (Laravel 10 + Inertia + Multi-tenant Subdomain)

## A) Environment and secrets
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY` (`php artisan key:generate --force`)
- [ ] `APP_URL` points to the production base URL
- [ ] Correct `DB_*` credentials and timezone settings
- [ ] Configure **SESSION**
  - [ ] `SESSION_DRIVER=redis` (recommended) or database/file
  - [ ] `SESSION_DOMAIN=.yourdomain.com` (for tenant subdomains)
  - [ ] `SESSION_SECURE_COOKIE=true` (HTTPS)
  - [ ] `SESSION_SAME_SITE=lax` (or `none` only if absolutely needed)

## B) Multi-tenant / subdomain
- [ ] Wildcard DNS / ingress (e.g. `*.yourdomain.com`) configured
- [ ] `SANCTUM_STATEFUL_DOMAINS` includes your domains if you use Sanctum SPA cookies
- [ ] Ensure tenant resolver supports:
  - [ ] subdomain tenant
  - [ ] root-domain tenant_code fallback
  - [ ] X-Tenant-Code header for mobile/automation clients

## C) Security hardening
- [ ] Force HTTPS (web server redirect + `TrustProxies` configured)
- [ ] Set security headers (CSP, HSTS, X-Frame-Options, etc.) at reverse proxy
- [ ] Enable CSRF on web routes; keep APIs on `auth:sanctum` with tokens for mobile
- [ ] Rate limit only sensitive endpoints (login, password reset); avoid throttling normal SPA JSON reads
- [ ] Run `php artisan config:cache` and `php artisan route:cache` (only after verifying routes/controllers exist)

## D) Storage and permissions
- [ ] `storage/` and `bootstrap/cache/` writable by web user
- [ ] `php artisan storage:link`
- [ ] Configure file storage disk (S3/minio/local) for tenant uploads

## E) Build and deploy
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] `php artisan optimize:clear` then `php artisan optimize`
- [ ] `npm ci` and `npm run build` (Vite)
- [ ] Queue worker (supervisor/systemd) if jobs are used
- [ ] Scheduler (cron) if scheduled tasks are used

## F) Observability
- [ ] Centralized logs (stdout, file rotation, or log shipping)
- [ ] Error monitoring (Sentry, etc.)
- [ ] Health endpoints behind auth / internal network

