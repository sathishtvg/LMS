<?php
namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\{CourseCompletion,Enrollment,Lesson,LessonProgress};
use App\Services\CertificateService;
use App\Services\EmailNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function show(Request $request, int $id)
    {
        $en = Enrollment::where('id',$id)->where('user_id',$request->user()->id)->firstOrFail();
        $progress = LessonProgress::where('enrollment_id',$en->id)->get()->keyBy('lesson_id');
        return response()->json(['enrollment'=>$en,'progress'=>$progress]);
    }

    public function upsert(Request $request, int $lessonId)
    {
        $data = $request->validate([
          'enrollment_id'=>'required|integer|exists:enrollments,id',
          'payload'=>'nullable|array',
        ]);

        $en = Enrollment::where('id',$data['enrollment_id'])->where('user_id',$request->user()->id)->firstOrFail();
        $lesson = Lesson::findOrFail($lessonId);

        // Ensure lesson belongs to this course
        $en->loadMissing(['course.modules.lessons']);
        $courseLessonIds = $en->course->modules->flatMap(fn($m)=>$m->lessons)->pluck('id')->all();
        if (!in_array($lesson->id, $courseLessonIds, true)) {
            return response()->json(['message'=>'Lesson not in this course'], 422);
        }

        // Sequential lock enforcement: cannot update progress for a locked lesson
        $orderedLessons = [];
        foreach ($en->course->modules->sortBy('sort_order') as $m) {
            foreach ($m->lessons->sortBy('sort_order') as $l) $orderedLessons[] = $l;
        }

        $progressByLesson = LessonProgress::where('enrollment_id',$en->id)->get()->keyBy('lesson_id');
        $blocked = false;
        foreach ($orderedLessons as $l) {
            $p = $progressByLesson->get($l->id);
            $completed = $p && ($p->completed_at || (int)$p->progress_percent >= 100);
            if ($l->id === $lesson->id) {
                if ($blocked) {
                    return response()->json([
                        'message' => 'Lesson locked. Complete previous required lessons first.',
                        'code' => 'LESSON_LOCKED'
                    ], 423);
                }
                break;
            }
            if (!$completed && ($l->required ?? true)) {
                $blocked = true;
            }
        }

        $payload = $data['payload'] ?? [];
        $percent = (int)($payload['progress_percent'] ?? $payload['percent'] ?? 0);
        $percent = max(0, min(100, $percent));

        // Apply min_watch_percent if set (video lessons)
        if (!empty($lesson->min_watch_percent) && $percent > 0) {
            // client sends actual watched percent; completion only at 100
        }

        $completedAt = null;
        if ($percent >= 100) {
            $completedAt = now();
        }

        $lp = LessonProgress::updateOrCreate(
          ['enrollment_id'=>$en->id,'lesson_id'=>$lesson->id],
          ['progress_percent'=>$percent,'completed_at'=>$completedAt,'meta_json'=>$payload]
        );

        // Update enrollment status
        if ($en->status === 'assigned') {
            $en->status = 'in_progress';
            $en->save();
        }

        // Course completion check: all required lessons completed
        $progressByLesson = LessonProgress::where('enrollment_id',$en->id)->get()->keyBy('lesson_id');
        $allRequiredCompleted = true;
        foreach ($orderedLessons as $l) {
            if (!($l->required ?? true)) continue;
            $p = $progressByLesson->get($l->id);
            $completed = $p && ($p->completed_at || (int)$p->progress_percent >= 100);
            if (!$completed) { $allRequiredCompleted = false; break; }
        }

        $certificate = null;
        $justCompleted = false;
        if ($allRequiredCompleted) {
            DB::transaction(function() use ($en, &$certificate) {
                // mark completed
                if ($en->status !== 'completed') {
                    $en->status = 'completed';
                    $en->save();
                    $justCompleted = true;
                }
                CourseCompletion::updateOrCreate(
                    ['enrollment_id' => $en->id],
                    ['completed_at' => now()]
                );

                // issue certificate (if enabled)
                $certificate = app(CertificateService::class)->issueForEnrollment($en);
            });
            if ($justCompleted) {
                app(EmailNotifications::class)->sendCourseCompleted($en->loadMissing(['user','course.translations']), $certificate);
            }
        }

        return response()->json([
            'lesson_progress' => $lp,
            'enrollment_status' => $en->status,
            'course_completed' => (bool)$allRequiredCompleted,
            'certificate' => $certificate,
        ]);
    }
}
