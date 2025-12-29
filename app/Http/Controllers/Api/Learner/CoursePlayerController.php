<?php

namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class CoursePlayerController extends Controller
{
    public function enrollment(Request $request, Enrollment $enrollment)
    {
        if ((int)$enrollment->user_id !== (int)$request->user()->id) abort(403);

        $enrollment->load([
            'course.translations',
            'course.modules.translations',
            'course.modules.lessons.translations',
            'course.modules.lessons.assets',
            'lessonProgress'
        ]);

        $progressByLesson = $enrollment->lessonProgress->keyBy('lesson_id');

        $orderedLessons = [];
        foreach ($enrollment->course->modules->sortBy('sort_order') as $m) {
            foreach ($m->lessons->sortBy('sort_order') as $l) $orderedLessons[] = $l;
        }

        $blocked = false;
        foreach ($orderedLessons as $lesson) {
            $lp = $progressByLesson->get($lesson->id);
            $completed = $lp && $lp->completed_at;
            $isLocked = $blocked;

            $lesson->setAttribute('progress', $lp);
            $lesson->setAttribute('is_locked', $isLocked);
            $lesson->setAttribute('unlock_reason', $isLocked ? 'Complete previous required lessons' : null);

            foreach ($lesson->assets as $asset) {
                $asset->setAttribute('access_url', $asset->is_external ? $asset->path_or_url : url($asset->path_or_url));
            }

            if (!$completed && ($lesson->required ?? true)) {
                $blocked = true;
            }
        }

        return response()->json($enrollment);
    }
}
