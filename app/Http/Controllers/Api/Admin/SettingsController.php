<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Backward compatible alias for older UI expecting /api/admin/settings (GET).
     */
    public function show(Request $request)
    {
        return $this->get($request);
    }

    /**
     * Returns current platform settings useful for the UI.
     */
    public function get(Request $request)
    {
        return [
            'storage' => [
                'mode' => config('filesystems.default'),
                'private_assets' => (bool) config('lms.private_assets', false),
            ],
            'uploads' => [
                'max_upload_mb' => (int) config('lms.max_upload_mb', 50),
                'allowed_pdf_mimes' => ['application/pdf'],
                'allowed_video_mimes' => ['video/mp4', 'video/quicktime', 'video/webm'],
            ],
            'mail' => [
                'driver' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ];
    }

    /**
     * Update settings (where supported by env-backed values).
     * Note: for production, prefer ENV + config caching. This endpoint is designed
     * for local/demo convenience and basic toggles.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'storage.mode' => 'nullable|string',
            'storage.private_assets' => 'nullable|boolean',
            'uploads.max_upload_mb' => 'nullable|integer|min:1|max:500',
        ]);

        // Persist to .env is out-of-scope here (hosting specific).
        // We store in database or settings table in later iteration.
        // For now, just echo validated payload as "saved".

        return [
            'ok' => true,
            'saved' => $data,
            'note' => 'For production, wire this to a settings store (DB) and sync env/config cache as required.'
        ];
    }

    /**
     * Basic storage health check.
     */
    public function storageHealth()
    {
        $ok = true;
        $disk = config('filesystems.default');
        $path = 'healthcheck.txt';

        try {
            Storage::disk($disk)->put($path, 'ok');
            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            $ok = false;
        }

        return [
            'ok' => $ok,
            'disk' => $disk,
        ];
    }

    /**
     * Send a test email using current mail configuration.
     */
    public function testEmail(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|email',
        ]);

        Mail::raw('LMS test email successful.', function ($message) use ($data) {
            $message->to($data['to'])
                ->subject('LMS Test Email');
        });

        return ['ok' => true];
    }
}
