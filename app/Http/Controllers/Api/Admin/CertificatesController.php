<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificatesController extends Controller
{
    public function index(Request $request)
    {
        // role middleware already enforces admin
        $q = Certificate::query()
            ->with([
                'user:id,name,email,phone',
                'course:id,code,default_language',
                'course.translations:course_id,lang,title'
            ])
            ->orderByDesc('issued_at');

        if ($request->filled('course_id')) {
            $q->where('course_id', (int)$request->course_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->whereHas('user', function ($uq) use ($term) {
                $uq->where('name', 'like', "%{$term}%")
                   ->orWhere('email', 'like', "%{$term}%")
                   ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $rows = $q->limit(1000)->get()->map(function ($c) {
            $title = null;
            if ($c->course) {
                $def = $c->course->default_language;
                $t = $c->course->translations?->firstWhere('lang', $def) ?: $c->course->translations?->first();
                $title = $t?->title;
            }
            return [
                'id' => $c->id,
                'certificate_no' => $c->certificate_no,
                'issued_at' => optional($c->issued_at)->toDateTimeString(),
                'expires_at' => optional($c->expires_at)->toDateTimeString(),
                'status' => $c->status,
                'pdf_path' => $c->pdf_path,
                'verify_url' => url('/verify/'.$c->verification_token),
                'course' => $title ?: ($c->course?->code),
                'user' => [
                    'name' => $c->user->name,
                    'email' => $c->user->email,
                    'phone' => $c->user->phone,
                ],
            ];
        });

        return response()->json($rows);
    }

    public function revoke(Request $request, $id)
    {
        $cert = Certificate::findOrFail($id);
        $cert->update(['status' => 'revoked']);
        return response()->json(['ok' => true]);
    }
}
