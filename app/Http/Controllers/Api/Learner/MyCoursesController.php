<?php
namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\{Enrollment,Course};
use App\Models\LessonProgress;
use Illuminate\Http\Request;

class MyCoursesController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $enrollments = Enrollment::where('user_id',$userId)->orderBy('id','desc')->get();

        $rows = $enrollments->map(function($en){
            $course = Course::with(['translations','modules.lessons'])->find($en->course_id);

            $lessonIds = $course?->modules?->flatMap(fn($m) => $m->lessons)->pluck('id')->all() ?? [];
            $requiredCount = $course?->modules?->flatMap(fn($m) => $m->lessons)->filter(fn($l) => ($l->required ?? true))->count() ?? 0;

            $completedRequired = 0;
            if (!empty($lessonIds)) {
                $completedRequired = LessonProgress::where('enrollment_id', $en->id)
                    ->whereIn('lesson_id', $lessonIds)
                    ->whereNotNull('completed_at')
                    ->count();
            }

            $progressPercent = $requiredCount > 0 ? min(100, floor(($completedRequired / $requiredCount) * 100)) : 0;
            $courseTitle = $course?->translations?->first()?->title;

            return [
                'id' => $en->id,
                'status' => $en->status,
                'due_date' => $en->due_date,
                'course_id' => $en->course_id,
                'course_title' => $courseTitle,
                'progress_percent' => $progressPercent,
                'course' => $course,
            ];
        });

        return response()->json($rows);
    }
}
