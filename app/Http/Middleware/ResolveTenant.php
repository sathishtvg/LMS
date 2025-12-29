<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $labels = explode('.', $host);
        $sub = $labels[0] ?? null;

        $isIp = (bool) preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host);
        $rootSubs = ['lms', 'localhost', 'www', null];
        $isRoot = $isIp || in_array($sub, $rootSubs, true);

        $tenant = null;

        // 1) Subdomain tenant
        if (!$isRoot && $sub) {
            $tenant = Tenant::where('code', $sub)->where('status', 'active')->first();
        }

        // 2) Header/input tenant code (mobile or root login)
        if (!$tenant) {
            $code = $request->header('X-Tenant-Code') ?: $request->input('tenant_code');
            if ($code) {
                $tenant = Tenant::where('code', $code)->where('status', 'active')->first();
            }
        }

        // 3) Session tenant (ONLY if session exists)
        if (!$tenant && $request->hasSession()) {
            $tid = $request->session()->get('tenant_id');
            if ($tid) {
                $tenant = Tenant::where('id', $tid)->where('status', 'active')->first();
            }
        }

        // 4) Optional local-dev default (avoid hard fail)
        if (!$tenant) {
            $tenant = Tenant::where('status', 'active')->orderBy('id')->first();
        }

        if ($tenant) {
            app()->instance('tenant', $tenant);
            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
