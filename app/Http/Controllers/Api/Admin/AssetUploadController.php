<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Lesson};
use App\Services\{AssetMetaExtractor,SystemSettings};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetUploadController extends Controller
{
    public function upload(Request $request, Lesson $lesson, SystemSettings $settings, AssetMetaExtractor $extractor)
    {
        $rules = $settings->get('uploads.rules', []);
        $maxMb = (int)($rules['max_mb'] ?? 50);
        $allowed = $rules['allowed_mimes'] ?? [];

        $data = $request->validate([
            'asset_type' => ['required', Rule::in(['video','pdf','ppt','image','other'])],
            'file' => ['required','file','max:' . ($maxMb * 1024)],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();

        $allowedMimes = $allowed[$data['asset_type']] ?? [];
        if (!in_array($mime, $allowedMimes, true)) {
            return response()->json([
                'message' => 'File type not allowed',
                'mime' => $mime,
                'allowed' => $allowedMimes,
            ], 422);
        }

        $storedPath = $file->store('public/lms-assets/lesson-' . $lesson->id);
        $storedPathNorm = str_starts_with($storedPath, 'public/') ? substr($storedPath, 7) : $storedPath;
        $publicUrlPath = '/storage/' . ltrim($storedPathNorm, '/');

        $meta = $extractor->extract($data['asset_type'], $file, $file->getRealPath());

        $asset = Asset::create([
            'lesson_id' => $lesson->id,
            'asset_type' => $data['asset_type'],
            'storage_driver' => 'local',
            'path_or_url' => $publicUrlPath,
            'is_external' => false,
            'meta_json' => $meta,
        ]);

        return response()->json(['asset'=>$asset], 201);
    }
}
