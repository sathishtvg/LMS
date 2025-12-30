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
| Only token-based endpoints live here (Sanctum token).
| Web/Inertia uses session JSON endpoints in routes/web_api.php
*/

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/me', [AuthApiController::class, 'me']);

        // Mobile courses/player
        Route::get('/courses', [CoursesApiController::class, 'index']);
        Route::get('/courses/{enrollment}', [PlayerApiController::class, 'player']);
        Route::post('/courses/{enrollment}/progress', [PlayerApiController::class, 'progress']);

        // Mobile assessments
        Route::get('/learner/assessments/{assessment}', [AssessmentPlayerController::class, 'show']);
        Route::post('/learner/assessments/{assessment}/submit', [AssessmentPlayerController::class, 'submit']);
    });
});
