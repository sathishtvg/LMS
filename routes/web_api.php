<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Admin\UsersAdminController;
use App\Http\Controllers\Api\Admin\CoursesAdminController;
use App\Http\Controllers\Api\Admin\EnrollmentsAdminController;
use App\Http\Controllers\Api\Admin\SettingsAdminController;

use App\Http\Controllers\Api\Learner\MyCoursesController;
use App\Http\Controllers\Api\Learner\PlayerController;
use App\Http\Controllers\Api\Learner\CertificatesController;
use App\Http\Controllers\Api\Learner\AssessmentPlayerController;

use App\Http\Controllers\Api\Web\Admin\CoursesWebApiController;
/**
 * IMPORTANT:
 * - These routes are for Inertia web app JSON calls.
 * - They use SESSION auth (auth) + tenant middleware.
 * - Do NOT put auth:sanctum here.
 * - Prefix is /api
 */

Route::prefix('api')->middleware(['web','auth','tenant'])->group(function () {

    // Optional: used by UI to confirm session user
    Route::get('/me', fn() => response()->json([
        'user' => auth()->user(),
    ]));

    Route::get('/courses', function () {
        return response()->json(['ok' => true, 'route' => '/api/courses works']);
    });

    // ============ ADMIN ============
    Route::middleware(['role:admin'])->group(function () {

        // Users CRUD
        Route::get('/users', [UsersAdminController::class, 'index']);
        Route::post('/users', [UsersAdminController::class, 'store']);
        Route::put('/users/{user}', [UsersAdminController::class, 'update']);
        Route::delete('/users/{user}', [UsersAdminController::class, 'destroy']);

        // Courses CRUD + builder endpoints
        Route::get('/courses', [CoursesAdminController::class, 'index']);          // <-- fixes api/courses
        Route::post('/courses', [CoursesAdminController::class, 'store']);
        Route::get('/courses/{course}', [CoursesAdminController::class, 'show']);
        Route::put('/courses/{course}', [CoursesAdminController::class, 'update']);
        Route::delete('/courses/{course}', [CoursesAdminController::class, 'destroy']);

        Route::post('/courses/{course}/modules', [CoursesAdminController::class, 'createModule']);
        Route::post('/courses/{course}/lessons', [CoursesAdminController::class, 'createLesson']);
        Route::post('/courses/{course}/reorder', [CoursesAdminController::class, 'reorder']);

        // Upload / attach asset to lesson
        Route::post('/assets/upload', [CoursesAdminController::class, 'upload']);
        Route::post('/lessons/{lesson}/attach', [CoursesAdminController::class, 'attachAsset']);

        // Enrollments
        Route::get('/admin/enrollments', [EnrollmentsAdminController::class, 'index']);
        Route::post('/admin/enrollments', [EnrollmentsAdminController::class, 'store']);
        Route::put('/admin/enrollments/{enrollment}', [EnrollmentsAdminController::class, 'update']);
        Route::delete('/admin/enrollments/{enrollment}', [EnrollmentsAdminController::class, 'destroy']);

        // Settings (upload rules etc.)
        Route::get('/admin/settings', [SettingsAdminController::class, 'show']);
        Route::put('/admin/settings', [SettingsAdminController::class, 'update']);
    });

    // ============ LEARNER ============
    Route::middleware(['role:learner'])->group(function () {

        // "My Courses"
        Route::get('/learner/my-courses', [MyCoursesController::class, 'index']);

        // Course player + progress
        Route::get('/learner/player/{enrollment}', [PlayerController::class, 'player']);
        Route::post('/learner/player/{enrollment}/progress', [PlayerController::class, 'progress']);

        // Certificates
        Route::get('/learner/certificates', [CertificatesController::class, 'index']);

        // Assessments
        Route::get('/learner/assessments/{assessment}', [AssessmentPlayerController::class, 'show']);
        Route::post('/learner/assessments/{assessment}/submit', [AssessmentPlayerController::class, 'submit']);
    });
});
