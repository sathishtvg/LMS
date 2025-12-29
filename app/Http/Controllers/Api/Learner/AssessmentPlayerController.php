<?php

namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssessmentPlayerController extends Controller
{
    public function show($assessmentId)
    {
        return response()->json([
            'assessment_id' => $assessmentId,
            'status' => 'ok',
        ]);
    }

    public function submit(Request $request, $assessmentId)
    {
        return response()->json([
            'assessment_id' => $assessmentId,
            'submitted' => true,
        ]);
    }
}
