<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        if (!$user || $user->role !== $role) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden'], 403)
                : abort(403);
        }
        return $next($request);
    }
}
