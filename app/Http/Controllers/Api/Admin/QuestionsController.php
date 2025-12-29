<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\QuestionTranslation;
use App\Models\OptionTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionsController extends Controller
{
  private function requireAdmin(Request $r): void {
    if ($r->user()->role !== 'admin') abort(403, 'Forbidden');
  }

  public function addQuestion(Request $r, QuestionBank $bank) {
    $this->requireAdmin($r);

    $data = $r->validate([
      'type' => 'required|in:mcq,truefalse,short',
      'is_critical' => 'boolean',
      'points' => 'integer|min:1|max:100',
      'sort_order' => 'integer|min:1',
      'question_text' => 'required|string',
      'lang' => 'nullable|in:en,ms,ta,zh',
      'options' => 'array',
      'options.*.text' => 'required_with:options|string',
      'options.*.is_correct' => 'boolean',
    ]);

    $lang = $data['lang'] ?? 'en';

    return DB::transaction(function () use ($bank, $data, $lang) {
      $q = Question::create([
        'bank_id' => $bank->id,
        'type' => $data['type'],
        'is_critical' => $data['is_critical'] ?? false,
        'points' => $data['points'] ?? 1,
        'sort_order' => $data['sort_order'] ?? (1 + (int)Question::where('bank_id',$bank->id)->max('sort_order')),
      ]);

      QuestionTranslation::create([
        'question_id' => $q->id,
        'lang' => $lang,
        'question_text' => $data['question_text'],
        'explanation_text' => null,
      ]);

      $opts = $data['options'] ?? [];
      $opts = $this->normalizeOptionsForType($data['type'], $opts);
      $i = 1;
      foreach ($opts as $opt) {
        $o = QuestionOption::create([
          'question_id' => $q->id,
          'is_correct' => (bool)($opt['is_correct'] ?? false),
          'sort_order' => $i++,
        ]);
        OptionTranslation::create([
          'option_id' => $o->id,
          'lang' => $lang,
          'option_text' => $opt['text'],
        ]);
      }

      return response()->json($q->load(['translations','options.translations']), 201);
    });
  }

  public function updateQuestion(Request $r, Question $question) {
    $this->requireAdmin($r);

    $data = $r->validate([
      'is_critical' => 'boolean',
      'points' => 'integer|min:1|max:100',
      'sort_order' => 'integer|min:1',
      'question_text' => 'nullable|string',
      'lang' => 'nullable|in:en,ms,ta,zh',
    ]);

    $question->update(collect($data)->only(['is_critical','points','sort_order'])->toArray());

    if (!empty($data['question_text'])) {
      $lang = $data['lang'] ?? 'en';
      QuestionTranslation::updateOrCreate(
        ['question_id'=>$question->id,'lang'=>$lang],
        ['question_text'=>$data['question_text'],'explanation_text'=>null]
      );
    }

    return response()->json($question->fresh());
  }

  public function setOptions(Request $r, Question $question) {
    $this->requireAdmin($r);

    $data = $r->validate([
      'lang' => 'nullable|in:en,ms,ta,zh',
      'options' => 'required|array|min:2',
      'options.*.id' => 'nullable|integer',
      'options.*.text' => 'required|string',
      'options.*.is_correct' => 'boolean',
    ]);
    $lang = $data['lang'] ?? 'en';

    return DB::transaction(function () use ($question, $data, $lang) {
      // delete existing options, re-create for simplicity (Phase 3 MVP)
      foreach ($question->options as $o) {
        $o->delete();
      }

      $opts = $this->normalizeOptionsForType($question->type, $data['options']);
      $i=1;
      foreach ($opts as $opt) {
        $o = QuestionOption::create([
          'question_id'=>$question->id,
          'is_correct'=>(bool)($opt['is_correct'] ?? false),
          'sort_order'=>$i++,
        ]);
        OptionTranslation::create([
          'option_id'=>$o->id,
          'lang'=>$lang,
          'option_text'=>$opt['text'],
        ]);
      }

      return response()->json($question->fresh()->load(['translations','options.translations']));
    });
  }

  public function reorder(Request $r, QuestionBank $bank) {
    $this->requireAdmin($r);
    $data = $r->validate([
      'question_ids' => 'required|array|min:1',
      'question_ids.*' => 'integer',
    ]);

    $ids = $data['question_ids'];
    return DB::transaction(function () use ($bank, $ids) {
      $existing = Question::where('bank_id', $bank->id)->pluck('id')->all();
      // Only reorder IDs that belong to this bank
      $ids = array_values(array_filter($ids, fn($id) => in_array($id, $existing)));
      $i = 1;
      foreach ($ids as $id) {
        Question::where('id',$id)->where('bank_id',$bank->id)->update(['sort_order'=>$i++]);
      }
      return response()->json(['ok'=>true]);
    });
  }

  public function deleteQuestion(Request $r, Question $question) {
    $this->requireAdmin($r);
    // Force delete so related options/translations are removed (FK cascade)
    $question->forceDelete();
    return response()->json(['ok'=>true]);
  }

  private function normalizeOptionsForType(string $type, array $opts): array
  {
    // For mcq/truefalse enforce at least 2 options and exactly 1 correct.
    if (in_array($type, ['mcq','truefalse'])) {
      $opts = array_values(array_filter($opts, fn($o) => isset($o['text']) && trim($o['text']) !== ''));
      if (count($opts) < 2) {
        // Keep at least 2 placeholders
        return $opts;
      }
      $correctIdx = null;
      foreach ($opts as $i => $o) {
        if (!empty($o['is_correct'])) { $correctIdx = $i; break; }
      }
      if ($correctIdx === null) $correctIdx = 0;
      foreach ($opts as $i => &$o) {
        $o['is_correct'] = ($i === $correctIdx);
      }
      unset($o);
      return $opts;
    }
    return $opts;
  }
}
