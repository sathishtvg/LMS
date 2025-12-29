<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthWebController extends Controller
{
    public function showLogin(Request $request)
{
    $host = $request->getHost();
    $labels = explode('.', $host);
    $sub = $labels[0] ?? null;

    $needTenantCode = in_array($sub, ['lms','localhost'], true) || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host);
    $tenant = app()->bound('tenant') ? app('tenant') : null;

    return inertia('Auth/Login', [
        'needTenantCode' => $needTenantCode,
        'tenantHint' => $tenant ? ['name' => $tenant->name, 'code' => $tenant->code] : null,
    ]);
}


public function login(Request $request)
{
    $data = $request->validate([
        'tenant_code' => 'nullable|string',
        'identifier'  => 'required|string',
        'password'    => 'required|string',
    ]);

    // ----------------------------
    // 1) Resolve tenant (subdomain first, then tenant_code fallback)
    // ----------------------------
    $host   = $request->getHost();          // acme.lms.test
    $labels = explode('.', $host);
    $sub    = $labels[0] ?? null;           // acme

    $isIp   = (bool) preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host);

    // Treat these as "root" (non-tenant) domains in local/dev
    $rootSubs = ['lms', 'localhost', 'www', null];
    $isRoot   = $isIp || in_array($sub, $rootSubs, true);

    if ($isRoot) {
        // Root domain login requires tenant_code
        $code = $data['tenant_code'] ?? null;
        if (!$code) {
            return back()->withErrors(['tenant_code' => 'Tenant code is required'])->withInput();
        }

        $tenant = Tenant::where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            return back()->withErrors(['tenant_code' => 'Invalid tenant code'])->withInput();
        }
    } else {
        // Subdomain login (acme.lms.test -> tenant code "acme")
        $tenant = Tenant::where('code', $sub)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            return back()->withErrors(['tenant_code' => 'Invalid tenant subdomain'])->withInput();
        }
    }

    // Persist tenant for this session + this request
    $request->session()->put('tenant_id', $tenant->id);
    app()->instance('tenant', $tenant);

    // ----------------------------
    // 2) Email or phone login
    // ----------------------------
    $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

    // IMPORTANT: enforce tenant_id at login to prevent cross-tenant login
    $credentials = [
        $field     => $data['identifier'],
        'password' => $data['password'],
        'status'   => 'active',
        'tenant_id'=> $tenant->id,
    ];

    if (!Auth::attempt($credentials)) {
        return back()->withErrors(['identifier' => 'Invalid credentials'])->withInput();
    }

    // ----------------------------
    // 3) Stabilize session (prevents 419/unauth issues)
    // ----------------------------
    $request->session()->regenerate();

    // DO NOT create Sanctum personal access token for web.
    // Web will use session cookie auth.

    return redirect()->route('app');
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('api_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
