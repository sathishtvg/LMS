# Stable v6 – Clean Diff (Key Changes)

This build is a stabilization pass focused on the recurring local issues you reported:
- **Unauthenticated** JSON responses after successful web login
- **419 Page Expired**
- **429 Too Many Requests** appearing on normal page/API loads
- Confusion about whether `routes/web_api.php` is being loaded
- Windows-local **session file write** errors due to missing `storage/framework/...` folders

## 1) Routing and middleware

### `routes/web_api.php`
- Added a **no-auth diagnostic endpoint**: `GET /api/__webapi_loaded`
  - Response includes `ok`, `tenant_id`, `host`, and current `user_id` (if any).
- Forced the web JSON API group to:
  - Run as **session-authenticated web routes**: `middleware(['web','tenant','auth'])`
  - Explicitly disable Laravel throttling middleware on this group:
    - `withoutMiddleware(ThrottleRequests::class)`
  - This prevents **429 Too Many Requests** if throttle middleware is being applied by mistake in your environment.

### `routes/web.php`
- Removed duplicate tenant middleware execution:
  - Changed `Route::middleware(['auth','tenant'])` to `Route::middleware(['auth'])`
  - Tenant resolution already runs inside the global `web` middleware group.

### `app/Http/Kernel.php`
- Registered a route middleware alias:
  - `'tenant' => App\Http\Middleware\ResolveTenant::class`
  - Avoids “Target class [tenant] does not exist” when a route references `tenant`.

## 2) Local session folder hardening (Windows-friendly)

### `app/Providers/AppServiceProvider.php`
- Added an idempotent boot-time guard that creates these folders if missing:
  - `storage/framework/sessions`
  - `storage/framework/cache`
  - `storage/framework/views`

This prevents:
- `file_put_contents(...storage/framework/sessions/...): Failed to open stream: No such file or directory`

## 3) Documentation updates

### `README.md` and `.env.example`
- Clarified the **required** setting for subdomain-based tenants:
  - `SESSION_DOMAIN=.lms.test`

If you access the app as `http://acme.lms.test:8000`, the session cookie must be valid for `.lms.test` to be shared across subdomains.
