<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificatesDownloadController extends Controller
{
    public function download(Request $request, int $id)
    {
        $user = $request->user();
        $cert = Certificate::findOrFail($id);

        // Admin can download any certificate; learner can download own only.
        if (($user->role ?? '') !== 'admin' && $cert->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$cert->pdf_path) {
            return response()->json(['message' => 'Certificate PDF not generated yet'], 409);
        }

        $relative = ltrim(str_replace('/storage/', '', $cert->pdf_path), '/');
        if (!Storage::disk('public')->exists($relative)) {
            return response()->json(['message' => 'Certificate file missing'], 404);
        }

        return Storage::disk('public')->download($relative, $cert->certificate_no . '.pdf');
    }
}
