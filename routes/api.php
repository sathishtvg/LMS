<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\Mobile\CoursesApiController;
use App\Http\Controllers\Api\Mobile\PlayerApiController;
use App\Http\Controllers\Api\Learner\AssessmentPlayerController;

/*
|--------------------------------------------------------------------------
| API Routes (Token / Mobile)
|--------------------------------------------------------------------------
| Mobile/external clients use Sanctum token auth under /api/v1.
| Web (Inertia) should use session-auth routes (see routes/web.php or routes/web_api.php).
*/

Route::prefix('v1')->group(function () {

    // Mobile login -> returns Sanctum token
    Route::post('/login', [AuthApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthApiController::class, 'me']);
        Route::post('/logout', [AuthApiController::class, 'logout']);

        // Courses / Player
        Route::get('/courses', [CoursesApiController::class, 'index']);
        Route::get('/courses/{enrollment}', [PlayerApiController::class, 'player']);
        Route::post('/courses/{enrollment}/progress', [PlayerApiController::class, 'progress']);

        // Assessments
        Route::get('/learner/assessments/{assessment}', [AssessmentPlayerController::class, 'show']);
        Route::post('/learner/assessments/{assessment}/submit', [AssessmentPlayerController::class, 'submit']);
    });
});
