# Safety Induction LMS — Web + API (Laravel) + Modern UI

This repository contains:
- Admin portal (Users/Courses/Enrollments/Assessments/Certificates/Reports/Settings)
- Learner portal (My Courses, Player, Assessment, Certificates wallet)
- REST API for Web + Mobile
- MySQL schema + seed data (Safety Induction demo)

## Requirements
- PHP 8.1+
- Composer
- Node 18+
- MySQL 8+

## Setup
1. `composer install`
2. `cp .env.example .env` and set DB credentials
3. `php artisan key:generate`
4. `php artisan migrate --seed`
5. `npm install`
6. `npm run dev`
7. `php artisan serve`

## Udemy-like player components
The learner player uses:
- `react-player` for video playback + time tracking
- `react-pdf` (+ `pdfjs-dist`) for PDF/PPT rendering + page visit tracking

After `npm install`, this works out of the box (PDF.js worker is configured in `resources/js/app.jsx`).

## Seeded test accounts
- Admin: admin@example.com / +6591111111 / Admin@12345
- Trainer: trainer@example.com / +6592222222 / Trainer@12345
- Learner: learner1@example.com / +6593333333 / Learner@12345

## Key rule: Non-skippable learning
- Sequential lock enabled for Safety Induction
- Videos require >= 90% watch
- PDFs require all pages visited
- API prevents assessment start until all required lessons complete

## Local Storage Setup (Required)
Run:
- `php artisan storage:link`

Uploaded assets will be stored under `storage/app/public/lms-assets/...` and served via `/storage/...`.

## Optional (metadata extraction)
Install:
- `composer require smalot/pdfparser james-heinrich/getid3`


## Multi-tenant (Shared DB) — Local Development

This build supports **tenant subdomain login** (e.g., `acme.lms.test`) with **tenant_code fallback** on the root domain (`lms.test`).

### 1) Hosts file
Add (examples):

**Windows:** `C:\Windows\System32\drivers\etc\hosts`
```
127.0.0.1 lms.test
127.0.0.1 acme.lms.test
127.0.0.1 beta.lms.test
```

### 2) Run backend
```
php artisan serve --host=0.0.0.0 --port=8000
```

### 3) .env (local)
```
APP_URL=http://lms.test:8000
SESSION_DOMAIN=.lms.test
SANCTUM_STATEFUL_DOMAINS=lms.test,acme.lms.test,beta.lms.test
SESSION_SECURE_COOKIE=false
```

### 4) Seeded tenants & users
- Tenant `acme` (subdomain `acme`)
- Tenant `beta` (subdomain `beta`)

Default credentials (tenant `acme`):
- admin: `admin@example.com` / `Admin@12345`
- trainer: `trainer@example.com` / `Trainer@12345`
- learner: `learner1@example.com` / `Learner@12345`

### 5) Login behavior
- `http://acme.lms.test:8000/login` → tenant auto-resolved from subdomain
- `http://lms.test:8000/login` → requires `tenant_code` (e.g. `acme`)

### Android (local dev)
Android emulator uses `10.0.2.2` to reach your PC.
The mobile app sends `X-Tenant-Code` automatically (`DEFAULT_TENANT_CODE=acme` in `lms-mobile-android/src/config.js`).

## Local run (subdomain + tenant_code)

1. Copy `.env.example` to `.env` and set:
   - `APP_URL=http://acme.lms.test:8000`
   - `SESSION_DRIVER=file`
   - `SESSION_DOMAIN=` (leave empty for local)
   - `SANCTUM_STATEFUL_DOMAINS=acme.lms.test,lms.test,localhost,127.0.0.1`
2. Hosts file:
   - `127.0.0.1 lms.test`
   - `127.0.0.1 acme.lms.test`
3. Run:
   - `composer install`
   - `php artisan key:generate`
   - `php artisan migrate --seed`
   - `npm install`
   - `npm run dev`
   - `php artisan serve --host=0.0.0.0 --port=8000`

Web JSON APIs used by Inertia are session-authenticated under `/api/...` and defined in `routes/web_api.php`.
