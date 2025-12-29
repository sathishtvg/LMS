<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function uploadLogo(Request $request, SystemSettings $settings)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'logo' => ['required','file','mimes:png,jpg,jpeg,webp','max:2048'],
        ]);

        $file = $request->file('logo');
        $path = $file->storeAs('branding', 'logo.' . $file->getClientOriginalExtension(), 'public');

        $branding = $settings->get('branding.config', [
            'portal_name' => 'LMS',
            'company_name' => 'LMS',
            'primary_color' => '#2563eb',
            'logo_path' => null,
        ]);
        $branding['logo_path'] = '/storage/' . $path;
        $settings->put('branding.config', $branding, (int)$request->user()->id);

        return response()->json(['logo_path' => $branding['logo_path']]);
    }
}
