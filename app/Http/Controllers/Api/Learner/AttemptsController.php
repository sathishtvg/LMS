<?php
namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\{Assessment,Enrollment,Attempt,AttemptAnswer,Question,LessonProgress,Course};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptsController extends Controller
{
  private function ensureCourseEligibleForAssessment(Enrollment $en, Course $course): array
  {
    $rules = $course->completion_rules_json ?? [];
    $sequential = (bool)($rules['sequential_lock'] ?? true);
    if (!$sequential) return ['ok'=>true];

    $lessonIds = DB::table('lessons')
      ->join('modules','modules.id','=','lessons.module_id')
      ->where('modules.course_id',$course->id)
      ->where('lessons.required',1)
      ->orderBy('modules.sort_order')
      ->orderBy('lessons.sort_order')
      ->pluck('lessons.id')
      ->toArray();

    $completed = LessonProgress::where('enrollment_id',$en->id)
      ->whereIn('lesson_id',$lessonIds)
      ->whereNotNull('completed_at')
      ->pluck('lesson_id')
      ->toArray();

    $missing = array_values(array_diff($lessonIds, $completed));
    if (count($missing) > 0) {
      return ['ok'=>false,'missing_required_lesson_ids'=>$missing];
    }
    return ['ok'=>true];
  }

  public function start(Request $request, int $assessmentId)
  {
    $data = $request->validate([
      'enrollment_id'=>'required|integer|exists:enrollments,id'
    ]);

    $assessment = Assessment::findOrFail($assessmentId);

    $en = Enrollment::where('id',$data['enrollment_id'])
      ->where('user_id',$request->user()->id)
      ->firstOrFail();

    if ($en->course_id !== $assessment->course_id) {
      return response()->json(['message'=>'Invalid assessment for this enrollment'], 422);
    }

    $course = Course::findOrFail($assessment->course_id);

    $gate = $this->ensureCourseEligibleForAssessment($en, $course);
    if (!$gate['ok']) {
      return response()->json([
        'message'=>'Complete all required lessons before starting the assessment.',
        'missing_required_lesson_ids'=>$gate['missing_required_lesson_ids']
      ], 423);
    }

    if ($assessment->attempts_limit) {
      $count = Attempt::where('assessment_id',$assessment->id)
        ->where('enrollment_id',$en->id)
        ->whereIn('status',['submitted','passed','failed'])
        ->count();
      if ($count >= $assessment->attempts_limit) {
        return response()->json(['message'=>'Attempts limit reached'], 429);
      }
    }

    $attempt = Attempt::create([
      'assessment_id'=>$assessment->id,
      'enrollment_id'=>$en->id,
      'started_at'=>now(),
      'status'=>'in_progress',
      'critical_wrong_count'=>0,
      'score_percent'=>0,
      'passed'=>false
    ]);

    return response()->json([
      'attempt'=>$attempt,
      'duration_sec'=>$assessment->duration_sec,
      'soft_timer'=>true
    ], 201);
  }

  public function questions(Request $request, int $attemptId)
  {
    $attempt = Attempt::with(['assessment.banks.questions.options','enrollment'])->findOrFail($attemptId);
    if ($attempt->enrollment->user_id !== $request->user()->id) abort(403,'Forbidden');

    $lang = $request->user()->language ?? 'en';

    $questions = [];
    foreach ($attempt->assessment->banks as $bank) {
      foreach ($bank->questions as $q) {
        $qt = $q->translations()->where('lang',$lang)->first()
          ?? $q->translations()->where('lang','en')->first();

        $opts = [];
        foreach ($q->options as $o) {
          $ot = $o->translations()->where('lang',$lang)->first()
            ?? $o->translations()->where('lang','en')->first();
          $opts[] = ['id'=>$o->id,'text'=>$ot?->option_text ?? ''];
        }

        $questions[] = [
          'id'=>$q->id,
          'type'=>$q->type,
          'is_critical'=>$q->is_critical,
          'points'=>$q->points,
          'text'=>$qt?->question_text ?? '',
          'options'=>$opts
        ];
      }
    }

    if ($attempt->assessment->shuffle_questions) shuffle($questions);
    if ($attempt->assessment->shuffle_options) {
      foreach ($questions as &$qq) {
        if (is_array($qq['options'])) shuffle($qq['options']);
      }
    }

    return response()->json([
      'attempt_id'=>$attempt->id,
      'assessment_id'=>$attempt->assessment_id,
      'questions'=>$questions
    ]);
  }

  public function submit(Request $request, int $attemptId)
  {
    $attempt = Attempt::with(['assessment','enrollment'])->findOrFail($attemptId);
    if ($attempt->enrollment->user_id !== $request->user()->id) abort(403,'Forbidden');

    if ($attempt->status !== 'in_progress') {
      return response()->json(['message'=>'Attempt already submitted'], 409);
    }

    $payload = $request->validate([
      'answers' => 'required|array',
      'answers.*.question_id' => 'required|integer|exists:questions,id',
      'answers.*.option_id' => 'nullable|integer|exists:question_options,id',
      'client_time_sec' => 'nullable|integer|min:0',
    ]);

    return DB::transaction(function () use ($attempt, $payload) {
      $assessment = $attempt->assessment;

      $totalPoints = 0;
      $earned = 0;
      $criticalWrong = 0;

      foreach ($payload['answers'] as $a) {
        $q = Question::with('options')->findOrFail($a['question_id']);
        $totalPoints += (int)$q->points;

        $selected = $a['option_id'] ?? null;
        $isCorrect = false;

        if ($selected) {
          $opt = $q->options->firstWhere('id', (int)$selected);
          $isCorrect = $opt ? (bool)$opt->is_correct : false;
        }

        $score = $isCorrect ? (int)$q->points : 0;
        $earned += $score;

        if (!$isCorrect && $q->is_critical) $criticalWrong++;

        AttemptAnswer::updateOrCreate(
          ['attempt_id'=>$attempt->id,'question_id'=>$q->id],
          ['answer_json'=>['option_id'=>$selected],'is_correct'=>$isCorrect,'score_awarded'=>$score]
        );
      }

      $scorePercent = $totalPoints > 0 ? round(($earned / $totalPoints) * 100, 2) : 0;

      $passed = $scorePercent >= (int)$assessment->pass_percent;
      if ($assessment->critical_enabled && $criticalWrong > (int)$assessment->allowed_critical_mistakes) {
        $passed = false;
      }

      $attempt->update([
        'submitted_at'=>now(),
        'score_percent'=>$scorePercent,
        'passed'=>$passed,
        'critical_wrong_count'=>$criticalWrong,
        'status'=>$passed ? 'passed' : 'failed',
      ]);

      return response()->json([
        'attempt'=>$attempt->fresh(),
        'score_percent'=>$scorePercent,
        'passed'=>$passed,
        'critical_wrong_count'=>$criticalWrong,
        'pass_percent'=>$assessment->pass_percent,
      ]);
    });
  }

  public function result(Request $request, int $attemptId)
  {
    $attempt = Attempt::with(['assessment','enrollment'])->findOrFail($attemptId);
    if ($attempt->enrollment->user_id !== $request->user()->id) abort(403,'Forbidden');

    return response()->json([
      'attempt'=>$attempt
    ]);
  }
}
