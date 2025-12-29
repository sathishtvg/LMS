<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\SystemSettings;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        /** @var SystemSettings $settings */
        $settings = app(SystemSettings::class);
        $branding = $settings->get('branding.config', [
            'portal_name' => 'LMS',
            'company_name' => 'LMS',
            'primary_color' => '#2563eb', // blue-600
            'logo_path' => null,
        ]);

        return array_merge(parent::share($request), [
            'auth' => [
                'api_token' => fn () => session('api_token'),
                'user' => $request->user() ? [
                    'id'=>$request->user()->id,
                    'name'=>$request->user()->name,
                    'role'=>$request->user()->role,
                    'language'=>$request->user()->language,
                ] : null,
            ],
            'branding' => $branding,
        ]);
    }
}
