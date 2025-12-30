<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Asset;

class CourseBuilderController extends Controller
{
    public function createModule(Request $request, Course $course)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'lang' => 'nullable|string|max:5',
        ]);

        $nextOrder = (int) Module::where('course_id', $course->id)->max('sort_order') + 1;

        $module = Module::create([
            'course_id' => $course->id,
            'sort_order' => $nextOrder,
        ]);

        // If you have module_translations table/model, save there.
        // Otherwise store title in module meta_json (if exists) or create translation model.
        if (method_exists($module, 'translations')) {
            $module->translations()->updateOrCreate(
                ['lang' => $data['lang'] ?? $course->default_language ?? 'EN'],
                ['title' => $data['title']]
            );
        } else {
            $module->update(['meta_json' => json_encode(['title' => $data['title']])]);
        }

        return response()->json(['module' => $module->fresh()]);
    }

    public function updateModule(Request $request, Module $module)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'lang' => 'nullable|string|max:5',
        ]);

        if (method_exists($module, 'translations')) {
            $module->translations()->updateOrCreate(
                ['lang' => $data['lang'] ?? 'EN'],
                ['title' => $data['title']]
            );
        } else {
            $module->update(['meta_json' => json_encode(['title' => $data['title']])]);
        }

        return response()->json(['module' => $module->fresh()]);
    }

    public function deleteModule(Module $module)
    {
        $module->delete();
        return response()->json(['ok' => true]);
    }

    public function createLesson(Request $request, Module $module)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,pdf,quiz,scorm,link',
            'required' => 'nullable|boolean',
            'min_watch_percent' => 'nullable|integer|min:0|max:100',
            'must_view_all_slides' => 'nullable|boolean',
            'lang' => 'nullable|string|max:5',
        ]);

        $nextOrder = (int) Lesson::where('module_id', $module->id)->max('sort_order') + 1;

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'type' => $data['type'],
            'sort_order' => $nextOrder,
            'required' => (bool)($data['required'] ?? true),
            'min_watch_percent' => $data['min_watch_percent'] ?? null,
            'must_view_all_slides' => (bool)($data['must_view_all_slides'] ?? false),
        ]);

        if (method_exists($lesson, 'translations')) {
            $lesson->translations()->updateOrCreate(
                ['lang' => $data['lang'] ?? 'EN'],
                ['title' => $data['title']]
            );
        } else {
            $lesson->update(['meta_json' => json_encode(['title' => $data['title']])]);
        }

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'required' => 'nullable|boolean',
            'min_watch_percent' => 'nullable|integer|min:0|max:100',
            'must_view_all_slides' => 'nullable|boolean',
            'lang' => 'nullable|string|max:5',
        ]);

        $lesson->update([
            'required' => $data['required'] ?? $lesson->required,
            'min_watch_percent' => array_key_exists('min_watch_percent', $data) ? $data['min_watch_percent'] : $lesson->min_watch_percent,
            'must_view_all_slides' => $data['must_view_all_slides'] ?? $lesson->must_view_all_slides,
        ]);

        if (!empty($data['title'])) {
            if (method_exists($lesson, 'translations')) {
                $lesson->translations()->updateOrCreate(
                    ['lang' => $data['lang'] ?? 'EN'],
                    ['title' => $data['title']]
                );
            } else {
                $lesson->update(['meta_json' => json_encode(['title' => $data['title']])]);
            }
        }

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    public function deleteLesson(Lesson $lesson)
    {
        $lesson->delete();
        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Course $course)
    {
        $data = $request->validate([
            'modules' => 'required|array',
            'modules.*.id' => 'required|integer',
            'modules.*.lessons' => 'nullable|array',
        ]);

        foreach ($data['modules'] as $mIndex => $m) {
            Module::where('id', $m['id'])->where('course_id', $course->id)
                ->update(['sort_order' => $mIndex + 1]);

            if (!empty($m['lessons'])) {
                foreach ($m['lessons'] as $lIndex => $l) {
                    Lesson::where('id', $l['id'])->update(['sort_order' => $lIndex + 1]);
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    public function uploadAsset(Request $request, Lesson $lesson)
    {
        $request->validate([
            'file' => 'required|file|max:512000', // 500MB example
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads', 'public');

        $asset = Asset::create([
            'lesson_id' => $lesson->id,
            'asset_type' => $file->getClientOriginalExtension(),
            'storage_driver' => 'local',
            'path_or_url' => $path,
            'is_external' => false,
            'meta_json' => json_encode([
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]),
        ]);

        return response()->json(['asset' => $asset]);
    }

    public function attachAsset(Request $request, Lesson $lesson)
    {
        // If you already uploaded and want to attach by ID
        $data = $request->validate([
            'asset_id' => 'required|integer|exists:assets,id',
        ]);

        $asset = Asset::findOrFail($data['asset_id']);
        $asset->update(['lesson_id' => $lesson->id]);

        return response()->json(['asset' => $asset->fresh()]);
    }

    public function deleteAsset(Asset $asset)
    {
        if ($asset->storage_driver === 'local' && $asset->path_or_url) {
            Storage::disk('public')->delete($asset->path_or_url);
        }
        $asset->delete();

        return response()->json(['ok' => true]);
    }
}
