<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\QuestionBank;
use Illuminate\Http\Request;

class AssessmentsController extends Controller
{
  private function requireAdmin(Request $r): void {
    if ($r->user()->role !== 'admin') abort(403, 'Forbidden');
  }

  public function index(Request $r) {
    $this->requireAdmin($r);
    $courseId = $r->query('course_id');
    $q = Assessment::query()->with('course:id,code');
    if ($courseId) $q->where('course_id',$courseId);
    return response()->json($q->orderByDesc('id')->get());
  }

  public function store(Request $r) {
    $this->requireAdmin($r);
    $data = $r->validate([
      'course_id' => 'required|exists:courses,id',
      'title' => 'required|string|max:190',
      'mode' => 'required|in:quiz,scenario',
      'duration_sec' => 'nullable|integer|min:0',
      'attempts_limit' => 'nullable|integer|min:1',
      'pass_percent' => 'required|integer|min:0|max:100',
      'critical_enabled' => 'boolean',
      'allowed_critical_mistakes' => 'integer|min:0',
      'shuffle_questions' => 'boolean',
      'shuffle_options' => 'boolean',
    ]);
    $assessment = Assessment::create($data);
    // Create default bank for convenience
    QuestionBank::create(['assessment_id'=>$assessment->id,'name'=>'Default Bank']);
    return response()->json($assessment->fresh(), 201);
  }

  public function update(Request $r, Assessment $assessment) {
    $this->requireAdmin($r);
    $data = $r->validate([
      'title' => 'sometimes|required|string|max:190',
      'mode' => 'sometimes|required|in:quiz,scenario',
      'duration_sec' => 'nullable|integer|min:0',
      'attempts_limit' => 'nullable|integer|min:1',
      'pass_percent' => 'sometimes|required|integer|min:0|max:100',
      'critical_enabled' => 'boolean',
      'allowed_critical_mistakes' => 'integer|min:0',
      'shuffle_questions' => 'boolean',
      'shuffle_options' => 'boolean',
      'rules_json' => 'nullable',
    ]);
    $assessment->update($data);
    return response()->json($assessment->fresh());
  }

  public function show(Request $r, Assessment $assessment) {
    $this->requireAdmin($r);
    $assessment->load([
      'course:id,code,title',
      'banks.questions.translations',
      'banks.questions.options.translations',
    ]);
    return response()->json($assessment);
  }
}
