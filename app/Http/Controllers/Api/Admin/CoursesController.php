<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Course,Module,Lesson,Asset,Enrollment,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EmailNotifications;

class CoursesController extends Controller
{
    public function index()
    {
        return response()->json(
            Course::with(['translations'])
                ->orderBy('id', 'desc')
                ->paginate(20)
        );
    }

    public function showFull(int $id)
    {
        $course = Course::with([
            'translations',
            'modules.translations',
            'modules.lessons.translations',
            'modules.lessons.assets'
        ])->findOrFail($id);

        $course->modules = $course->modules->sortBy('sort_order')->values();
        foreach ($course->modules as $m) {
            $m->lessons = $m->lessons->sortBy('sort_order')->values();
        }

        return response()->json(['course' => $course]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'=>'required|string|max:50|unique:courses,code',
            'status'=>'required|in:draft,published,archived',
            'default_language'=>'required|in:en,ms,ta,zh',
            'available_languages_json'=>'required|array',
            'completion_rules_json'=>'required|array',
            'certificate_enabled'=>'required|boolean',
            'certificate_validity_json'=>'nullable|array',
            'template_id'=>'nullable|integer'
        ]);
        $data['created_by'] = $request->user()->id;
        $course = Course::create($data);

        // Create default translation (title/description) if provided
        if ($request->has('title')) {
            $course->translations()->create([
                'lang' => $data['default_language'],
                'title' => (string)$request->input('title'),
                'description' => (string)($request->input('description') ?? ''),
            ]);
        }
        return response()->json(['course'=>$course], 201);
    }

    public function update(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        $data = $request->validate([
            'status'=>'sometimes|required|in:draft,published,archived',
            'default_language'=>'sometimes|required|in:en,ms,ta,zh',
            'available_languages_json'=>'sometimes|required|array',
            'completion_rules_json'=>'sometimes|required|array',
            'certificate_enabled'=>'sometimes|required|boolean',
            'certificate_validity_json'=>'nullable|array',
            'template_id'=>'nullable|integer'
        ]);
        $course->fill($data)->save();
        return response()->json(['course'=>$course]);
    }

    public function addModule(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        $data = $request->validate([
            'sort_order'=>'required|integer|min:1',
            'title'=>'nullable|string|max:255'
        ]);
        $module = Module::create(['course_id'=>$course->id,'sort_order'=>$data['sort_order']]);

        if (!empty($data['title'])) {
            $module->translations()->create([
                'lang' => $course->default_language,
                'title' => $data['title'],
            ]);
        }
        return response()->json(['module'=>$module], 201);
    }

    public function addLesson(Request $request, Module $module)
    {
        $data = $request->validate([
            'type' => 'required|in:video,pdf,ppt,other',
            'sort_order' => 'required|integer|min:1',
            'required' => 'required|boolean',
            'min_watch_percent' => 'nullable|integer|min:1|max:100',
            'must_view_all_slides' => 'required|boolean',
            'title' => 'nullable|string|max:255',
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'type' => $data['type'],
            'sort_order' => $data['sort_order'],
            'required' => $data['required'],
            'min_watch_percent' => $data['min_watch_percent'] ?? null,
            'must_view_all_slides' => $data['must_view_all_slides'],
        ]);

        if (!empty($data['title'])) {
            // Determine course default language
            $courseLang = DB::table('courses')->where('id', $module->course_id)->value('default_language') ?: 'en';
            $lesson->translations()->create([
                'lang' => $courseLang,
                'title' => $data['title'],
                'description' => '',
            ]);
        }

        return response()->json(['lesson' => $lesson], 201);
    }

    public function reorderModules(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        $data = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer'
        ]);

        $ids = $data['ordered_ids'];
        $existing = $course->modules()->whereIn('id', $ids)->pluck('id')->all();
        if (count($existing) !== count($ids)) {
            return response()->json(['message' => 'Invalid module list'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $mid) {
                Module::where('id', $mid)->update(['sort_order' => $i + 1]);
            }
        });

        return response()->json(['message' => 'OK']);
    }

    public function reorderLessons(Request $request, Module $module)
    {
        $data = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer'
        ]);
        $ids = $data['ordered_ids'];
        $existing = $module->lessons()->whereIn('id', $ids)->pluck('id')->all();
        if (count($existing) !== count($ids)) {
            return response()->json(['message' => 'Invalid lesson list'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $lid) {
                Lesson::where('id', $lid)->update(['sort_order' => $i + 1]);
            }
        });

        return response()->json(['message' => 'OK']);
    }

    public function addAsset(Request $request, int $id)
    {
        $lesson = Lesson::findOrFail($id);
        $data = $request->validate([
          'asset_type'=>'required|in:video,pdf,ppt,image,other',
          'path_or_url'=>'required|string',
          'is_external'=>'required|boolean',
          'meta_json'=>'nullable|array'
        ]);
        $asset = Asset::create([
          'lesson_id'=>$lesson->id,
          'asset_type'=>$data['asset_type'],
          'storage_driver'=>config('filesystems.default'),
          'path_or_url'=>$data['path_or_url'],
          'is_external'=>$data['is_external'],
          'meta_json'=>$data['meta_json'] ?? []
        ]);
        return response()->json(['asset'=>$asset], 201);
    }

    public function assignLearners(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        $data = $request->validate([
          'user_ids'=>'required|array|min:1',
          'user_ids.*'=>'integer|exists:users,id',
          'due_date'=>'nullable|date'
        ]);

        $assigned = [];
        foreach ($data['user_ids'] as $uid) {
          $en = Enrollment::firstOrCreate(
            ['course_id'=>$course->id,'user_id'=>$uid],
            [
              'status'=>'assigned',
              'assigned_by'=>$request->user()->id,
              'assigned_at'=>now(),
              'due_date'=>$data['due_date'] ?? null
            ]
          );
          $assigned[] = $en;
if ($en->wasRecentlyCreated) {
  // notify learner (if enabled)
  app(EmailNotifications::class)->sendEnrollmentAssigned($en->loadMissing(['user','course.translations']));
}
        }
        return response()->json(['enrollments'=>$assigned]);
    }
}
