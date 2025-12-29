<?php

namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificatesController extends Controller
{
    public function index(Request $request)
    {
        $certs = Certificate::query()
            ->where('user_id', $request->user()->id)
            ->with(['course:id,code'])
            ->orderByDesc('issued_at')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'certificate_no' => $c->certificate_no,
                    'issued_at' => optional($c->issued_at)->toDateTimeString(),
                    'expires_at' => optional($c->expires_at)->toDateTimeString(),
                    'status' => $c->status,
                    'course_code' => $c->course?->code,
                    'pdf_path' => $c->pdf_path,
                    'verify_url' => url('/verify/'.$c->verification_token),
                ];
            });

        return response()->json(['data' => $certs]);
    }

    public function download(Request $request, int $id)
    {
        $cert = Certificate::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

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
