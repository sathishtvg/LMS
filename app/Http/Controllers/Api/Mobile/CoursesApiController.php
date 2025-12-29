<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class CoursesApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return Enrollment::with(['course.modules.lessons.asset'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }
}
