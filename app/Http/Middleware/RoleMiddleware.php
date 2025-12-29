<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Alias middleware for routes using `role:...`.
 *
 * This project already contains EnsureRole; RoleMiddleware simply forwards to it
 * to keep backward compatibility with existing route definitions.
 */
class RoleMiddleware extends EnsureRole
{
    // Inherit handle() from EnsureRole
}
