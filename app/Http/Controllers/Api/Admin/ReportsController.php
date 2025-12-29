<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function completions(Request $request)
    {
        $this->requireAdmin($request);

        $q = Enrollment::query()
            ->with(['user:id,name,email,phone', 'course:id,code'])
            ->orderByDesc('assigned_at');

        if ($request->filled('course_id')) $q->where('course_id', $request->course_id);
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('from')) $q->whereDate('assigned_at', '>=', $request->from);
        if ($request->filled('to')) $q->whereDate('assigned_at', '<=', $request->to);

        $rows = $q->limit(1000)->get()->map(function ($e) {
            $completedAt = optional(\App\Models\CourseCompletion::where('enrollment_id', $e->id)->first())->completed_at;
            return [
                'enrollment_id' => $e->id,
                'learner_name' => $e->user?->name,
                'email' => $e->user?->email,
                'phone' => $e->user?->phone,
                'course_code' => $e->course?->code,
                'status' => $e->status,
                'assigned_at' => optional($e->assigned_at)->toDateTimeString(),
                'due_date' => $e->due_date,
                'completed_at' => optional($completedAt)->toDateTimeString(),
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function exportCompletions(Request $request): StreamedResponse
    {
        $this->requireAdmin($request);

        $filename = 'completion_report_' . date('Ymd_His') . '.csv';

        $q = Enrollment::query()
            ->with(['user:id,name,email,phone', 'course:id,code'])
            ->orderByDesc('assigned_at');

        if ($request->filled('course_id')) $q->where('course_id', $request->course_id);
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('from')) $q->whereDate('assigned_at', '>=', $request->from);
        if ($request->filled('to')) $q->whereDate('assigned_at', '<=', $request->to);

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Learner Name','Email','Phone','Course Code','Status','Assigned At','Due Date','Completed At']);

            $q->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $e) {
                    $cc = \App\Models\CourseCompletion::where('enrollment_id', $e->id)->first();
                    fputcsv($out, [
                        $e->user?->name,
                        $e->user?->email,
                        $e->user?->phone,
                        $e->course?->code,
                        $e->status,
                        optional($e->assigned_at)->toDateTimeString(),
                        $e->due_date,
                        optional($cc?->completed_at)->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }



public function assessments(Request $request)
{
    $this->requireAdmin($request);

    $q = Attempt::query()
        ->select([
            'attempts.id as attempt_id',
            'attempts.assessment_id',
            'attempts.enrollment_id',
            'attempts.started_at',
            'attempts.submitted_at',
            'attempts.score_percent',
            'attempts.passed',
            'attempts.critical_wrong_count',
            'assessments.title as assessment_title',
            'courses.id as course_id',
            'courses.code as course_code',
            'users.name as learner_name',
            'users.email as learner_email',
            'users.phone as learner_phone',
        ])
        ->join('assessments', 'assessments.id', '=', 'attempts.assessment_id')
        ->join('courses', 'courses.id', '=', 'assessments.course_id')
        ->join('enrollments', 'enrollments.id', '=', 'attempts.enrollment_id')
        ->join('users', 'users.id', '=', 'enrollments.user_id')
        ->orderByDesc('attempts.started_at');

    if ($request->filled('course_id')) {
        $q->where('courses.id', (int)$request->course_id);
    }
    if ($request->filled('assessment_id')) {
        $q->where('assessments.id', (int)$request->assessment_id);
    }
    if ($request->filled('passed')) {
        $q->where('attempts.passed', (int)$request->passed);
    }
    if ($request->filled('from')) {
        $q->whereDate('attempts.started_at', '>=', $request->from);
    }
    if ($request->filled('to')) {
        $q->whereDate('attempts.started_at', '<=', $request->to);
    }
    if ($request->filled('search')) {
        $s = trim($request->search);
        $q->where(function($qq) use ($s){
            $qq->where('users.name', 'like', "%{$s}%")
               ->orWhere('users.email', 'like', "%{$s}%")
               ->orWhere('users.phone', 'like', "%{$s}%")
               ->orWhere('courses.code', 'like', "%{$s}%")
               ->orWhere('assessments.title', 'like', "%{$s}%");
        });
    }

    $summary = (clone $q)
        ->selectRaw('COUNT(*) as attempts_count')
        ->selectRaw('AVG(score_percent) as avg_score')
        ->selectRaw('SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as pass_count')
        ->first();

    $rows = $q->limit(2000)->get()->map(function ($r) {
        return [
            'attempt_id' => (int)$r->attempt_id,
            'course_id' => (int)$r->course_id,
            'course' => $r->course_code,
            'assessment_id' => (int)$r->assessment_id,
            'assessment' => $r->assessment_title,
            'learner_name' => $r->learner_name,
            'email' => $r->learner_email,
            'phone' => $r->learner_phone,
            'started_at' => optional($r->started_at)->toDateTimeString(),
            'submitted_at' => optional($r->submitted_at)->toDateTimeString(),
            'score_percent' => $r->score_percent !== null ? (float)$r->score_percent : null,
            'passed' => (bool)$r->passed,
            'critical_wrong_count' => (int)$r->critical_wrong_count,
        ];
    });

    $attemptsCount = (int)($summary->attempts_count ?? 0);
    $passCount = (int)($summary->pass_count ?? 0);
    $avgScore = $summary && $summary->avg_score !== null ? round((float)$summary->avg_score, 2) : null;

    return response()->json([
        'summary' => [
            'attempts' => $attemptsCount,
            'passes' => $passCount,
            'fails' => max(0, $attemptsCount - $passCount),
            'pass_rate' => $attemptsCount > 0 ? round(($passCount / $attemptsCount) * 100, 2) : null,
            'avg_score' => $avgScore,
        ],
        'rows' => $rows,
    ]);
}

public function exportAssessments(Request $request): StreamedResponse
{
    $this->requireAdmin($request);

    $filename = 'assessment_report_' . date('Ymd_His') . '.csv';

    $q = Attempt::query()
        ->select([
            'attempts.started_at',
            'attempts.submitted_at',
            'attempts.score_percent',
            'attempts.passed',
            'attempts.critical_wrong_count',
            'assessments.title as assessment_title',
            'courses.code as course_code',
            'users.name as learner_name',
            'users.email as learner_email',
            'users.phone as learner_phone',
        ])
        ->join('assessments', 'assessments.id', '=', 'attempts.assessment_id')
        ->join('courses', 'courses.id', '=', 'assessments.course_id')
        ->join('enrollments', 'enrollments.id', '=', 'attempts.enrollment_id')
        ->join('users', 'users.id', '=', 'enrollments.user_id')
        ->orderByDesc('attempts.started_at');

    if ($request->filled('course_id')) $q->where('courses.id', (int)$request->course_id);
    if ($request->filled('assessment_id')) $q->where('assessments.id', (int)$request->assessment_id);
    if ($request->filled('passed')) $q->where('attempts.passed', (int)$request->passed);
    if ($request->filled('from')) $q->whereDate('attempts.started_at', '>=', $request->from);
    if ($request->filled('to')) $q->whereDate('attempts.started_at', '<=', $request->to);
    if ($request->filled('search')) {
        $s = trim($request->search);
        $q->where(function($qq) use ($s){
            $qq->where('users.name', 'like', "%{$s}%")
               ->orWhere('users.email', 'like', "%{$s}%")
               ->orWhere('users.phone', 'like', "%{$s}%")
               ->orWhere('courses.code', 'like', "%{$s}%")
               ->orWhere('assessments.title', 'like', "%{$s}%");
        });
    }

    return response()->streamDownload(function () use ($q) {
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Learner Name','Email','Phone','Course','Assessment','Started At','Submitted At',
            'Score (%)','Passed','Critical Wrong Count'
        ]);

        $q->chunk(500, function ($chunk) use ($out) {
            foreach ($chunk as $r) {
                fputcsv($out, [
                    $r->learner_name,
                    $r->learner_email,
                    $r->learner_phone,
                    $r->course_code,
                    $r->assessment_title,
                    optional($r->started_at)->toDateTimeString(),
                    optional($r->submitted_at)->toDateTimeString(),
                    $r->score_percent,
                    $r->passed ? 'Yes' : 'No',
                    $r->critical_wrong_count,
                ]);
            }
        });

        fclose($out);
    }, $filename, ['Content-Type' => 'text/csv']);
}

    private function requireAdmin(Request $request): void
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }
}
