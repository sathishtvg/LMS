<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class PlayerApiController extends Controller
{
    public function player(Request $request, Enrollment $enrollment)
    {
        $this->authorizeEnrollment($request, $enrollment);
        return $enrollment->load(['course.modules.lessons.asset','progress']);
    }

    public function progress(Request $request, Enrollment $enrollment)
    {
        $this->authorizeEnrollment($request, $enrollment);

        $data = $request->validate([
            'lesson_id' => 'required|integer',
            'seconds_watched' => 'nullable|integer',
            'pdf_pages_viewed' => 'nullable|integer',
            'completed' => 'nullable|boolean',
        ]);

        if (method_exists($enrollment, 'recordProgress')) {
            $enrollment->recordProgress($request->user(), $data);
            return ['ok' => true];
        }

        return ['ok' => true, 'note' => 'Progress accepted (implement recordProgress() for persistence).'];
    }

    private function authorizeEnrollment(Request $request, Enrollment $enrollment): void
    {
        abort_unless($enrollment->user_id === $request->user()->id, 403);
    }
}
