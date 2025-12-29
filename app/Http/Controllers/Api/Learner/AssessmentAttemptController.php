<?php
namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Enrollment;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentAttemptController extends Controller
{
  public function start(Request $r, Assessment $assessment)
  {
    $user = $r->user();

    $data = $r->validate([
      'enrollment_id' => 'required|exists:enrollments,id'
    ]);

    $enrollment = Enrollment::with(['course'])->findOrFail($data['enrollment_id']);
    if ($enrollment->user_id !== $user->id) abort(403,'Forbidden');

    // sequential lock: assessment unlocked only after all lessons complete (Phase 3)
    // If course has sequential lock, require enrollment completed before assessment
    $rules = $enrollment->course->completion_rules_json ?? [];
    $requiresAllLessons = ($rules['must_complete_all_lessons'] ?? true) ? true : false;
    if ($requiresAllLessons && !$enrollment->completed_at) {
      return response()->json(['message'=>'Assessment locked until course lessons completed'], 423);
    }

    // attempts limit
    if ($assessment->attempts_limit) {
      $count = Attempt::where('assessment_id',$assessment->id)->where('enrollment_id',$enrollment->id)->count();
      if ($count >= $assessment->attempts_limit) {
        return response()->json(['message'=>'Attempts limit reached'], 429);
      }
    }

    $attempt = Attempt::create([
      'assessment_id'=>$assessment->id,
      'enrollment_id'=>$enrollment->id,
      'started_at'=>now(),
      'status'=>'in_progress',
    ]);

    return response()->json([
      'attempt_id'=>$attempt->id,
      'duration_sec'=>$assessment->duration_sec,
      'soft_timer'=>true,
    ], 201);
  }

  public function questions(Request $r, Attempt $attempt)
  {
    $user = $r->user();
    $attempt->load('assessment.banks.questions.options');
    if ($attempt->enrollment->user_id !== $user->id) abort(403,'Forbidden');

    $lang = $user->language ?? 'en';

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
          'options'=>$opts,
        ];
      }
    }

    if ($attempt->assessment->shuffle_questions) shuffle($questions);
    if ($attempt->assessment->shuffle_options) {
      foreach ($questions as &$qq) {
        if (is_array($qq['options'])) shuffle($qq['options']);
      }
    }

    return response()->json(['attempt_id'=>$attempt->id,'questions'=>$questions]);
  }

  public function submit(Request $r, Attempt $attempt)
  {
    $user = $r->user();
    $attempt->load(['assessment','enrollment']);
    if ($attempt->enrollment->user_id !== $user->id) abort(403,'Forbidden');
    if ($attempt->status !== 'in_progress') {
      return response()->json(['message'=>'Attempt already submitted'], 409);
    }

    $payload = $r->validate([
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

      $passed = $scorePercent >= $assessment->pass_percent;
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
        'attempt_id'=>$attempt->id,
        'score_percent'=>$scorePercent,
        'passed'=>$passed,
        'critical_wrong_count'=>$criticalWrong,
        'pass_percent'=>$assessment->pass_percent,
      ]);
    });
  }

  public function result(Request $r, Attempt $attempt)
  {
    $user = $r->user();
    $attempt->load(['assessment','answers']);
    if ($attempt->enrollment->user_id !== $user->id) abort(403,'Forbidden');

    return response()->json([
      'attempt_id'=>$attempt->id,
      'status'=>$attempt->status,
      'score_percent'=>$attempt->score_percent,
      'passed'=>$attempt->passed,
      'critical_wrong_count'=>$attempt->critical_wrong_count,
      'pass_percent'=>$attempt->assessment->pass_percent,
    ]);
  }
}
