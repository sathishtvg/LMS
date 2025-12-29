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
| Web (Inertia) uses session-authenticated JSON routes defined in routes/web_api.php.
*/

Route::post('/v1/login', [AuthApiController::class, 'login']);
Route::post('/v1/logout', [AuthApiController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/login', [AuthApiController::class, 'login']);
Route::middleware('auth:sanctum')->get('/me', [AuthApiController::class, 'me']);
Route::middleware('auth:sanctum')->post('/logout', [AuthApiController::class, 'logout']);


Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/me', fn (Request $r) => $r->user());
    Route::get('/courses', [CoursesApiController::class, 'index']);
    Route::get('/courses/{enrollment}', [PlayerApiController::class, 'player']);
    Route::post('/courses/{enrollment}/progress', [PlayerApiController::class, 'progress']);
    Route::get('/learner/assessments/{assessment}', [AssessmentPlayerController::class, 'show']);
    Route::post('/learner/assessments/{assessment}/submit', [AssessmentPlayerController::class, 'submit']);

});
