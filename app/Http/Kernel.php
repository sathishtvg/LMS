<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack.
     */
    protected $middleware = [
        // Trust proxies (Laravel 10 default)
        \App\Http\Middleware\TrustProxies::class,

        // Handle CORS
        \Illuminate\Http\Middleware\HandleCors::class,

        // Maintenance mode
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,

        // Validate post size
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,

        // Trim strings
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,

        // Convert empty strings to null
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Middleware groups.
     */
    protected $middlewareGroups = [

        /**
         * WEB
         * Used for:
         * - Login
         * - Inertia UI
         * - routes/web.php
         * - routes/web_api.php
         */
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,

            // Required for session-based auth
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            // CSRF protection
            \App\Http\Middleware\VerifyCsrfToken::class,

            // Route bindings
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        /**
         * API
         * Used ONLY for:
         * - Mobile
         * - Token-based requests (/api/v1/*)
         */
        'api' => [
            // IMPORTANT:
            // We intentionally DO NOT use throttle:api here
            // to avoid 429 loops for subdomain tenants.

            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Route middleware aliases.
     */
    protected $middlewareAliases = [

        // Auth
        'auth' => \App\Http\Middleware\Authenticate::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

        // Tenant resolution (subdomain / header / session)
        'tenant' => \App\Http\Middleware\ResolveTenant::class,

        // Role-based access
        'role' => \App\Http\Middleware\RoleMiddleware::class,

        // Throttling (ONLY use explicitly)
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // Signed URLs
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
    ];
}
