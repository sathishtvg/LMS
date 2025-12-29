<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Api\Admin\CoursesController as AdminCoursesController;
use App\Http\Controllers\Api\Admin\EnrollmentsController as AdminEnrollmentsController;
use App\Http\Controllers\Api\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\Admin\CertificatesController as AdminCertificatesController;
use App\Http\Controllers\Api\Admin\AssessmentsController as AdminAssessmentsController;
use App\Http\Controllers\Api\Admin\QuestionsController as AdminQuestionsController;
use App\Http\Controllers\Api\Admin\ReportsController as AdminReportsController;

use App\Http\Controllers\Api\Learner\MyCoursesController;
use App\Http\Controllers\Api\Learner\CoursePlayerController;
use App\Http\Controllers\Api\Learner\ProgressController;
use App\Http\Controllers\Api\Learner\CertificatesController as LearnerCertificatesController;
use App\Http\Controllers\Api\Learner\CourseAssessmentsController;
use App\Http\Controllers\Api\Learner\AssessmentAttemptController;

use App\Http\Controllers\Api\Public\VerifyCertificateController;

/*
|--------------------------------------------------------------------------
| Session-authenticated JSON routes for WEB (Inertia/React)
|--------------------------------------------------------------------------
| These run under the "web" middleware group (sessions + CSRF),
| so they DO NOT suffer from API throttle and Sanctum token mismatch.
*/

// Health/diagnostic endpoint (no auth) – confirms web_api.php is loaded.
Route::get('/api/__webapi_loaded', function (Request $r) {
    return response()->json([
        'ok' => true,
        'host' => $r->getHost(),
        'tenant_id' => session('tenant_id'),
    ]);
});

Route::middleware(['auth', 'tenant'])
    // Extra safety: even if a throttle middleware is applied elsewhere, do not throttle these web-session APIs.
    ->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class])
    ->group(function () {

    Route::prefix('api')->group(function () {

        // Current user (useful for UI bootstrap)
        Route::get('/me', function (Request $r) {
            return response()->json([
                'user' => $r->user(),
                'tenant_id' => session('tenant_id'),
            ]);
        });

        // Backward compat: UI calling /api/users should not crash
        // If admin -> list users, else -> return current user.
        Route::get('/users', function (Request $r) {
            $u = $r->user();
            if ($u && $u->role === 'admin') {
                return app(AdminUsersController::class)->index($r);
            }
            return response()->json(['user' => $u]);
        });

        // Backward compat: UI calling /api/courses
        Route::get('/courses', function (Request $r) {
            $u = $r->user();
            if ($u && $u->role === 'admin') {
                return app(AdminCoursesController::class)->index($r);
            }
            return app(MyCoursesController::class)->index($r);
        });

        /*
        |--------------------------------------------------------------------------
        | ADMIN APIs
        |--------------------------------------------------------------------------
        */
        Route::prefix('admin')->middleware(['role:admin'])->group(function () {
            Route::get('/users', [AdminUsersController::class, 'index']);
            Route::post('/users', [AdminUsersController::class, 'store']);
            Route::put('/users/{user}', [AdminUsersController::class, 'update']);
            Route::delete('/users/{user}', [AdminUsersController::class, 'destroy']);

            Route::get('/courses', [AdminCoursesController::class, 'index']);
            Route::post('/courses', [AdminCoursesController::class, 'store']);
            Route::get('/courses/{course}', [AdminCoursesController::class, 'show']);
            Route::put('/courses/{course}', [AdminCoursesController::class, 'update']);
            Route::delete('/courses/{course}', [AdminCoursesController::class, 'destroy']);

            Route::get('/enrollments', [AdminEnrollmentsController::class, 'index']);
            Route::post('/enrollments', [AdminEnrollmentsController::class, 'store']);
            Route::patch('/enrollments/{enrollment}', [AdminEnrollmentsController::class, 'update']);
            Route::delete('/enrollments/{enrollment}', [AdminEnrollmentsController::class, 'destroy']);

            Route::get('/settings', [AdminSettingsController::class, 'show']);
            Route::put('/settings', [AdminSettingsController::class, 'update']);

            Route::get('/certificates', [AdminCertificatesController::class, 'index']);

            Route::get('/assessments', [AdminAssessmentsController::class, 'index']);
            Route::post('/assessments', [AdminAssessmentsController::class, 'store']);
            Route::get('/assessments/{assessment}', [AdminAssessmentsController::class, 'show']);
            Route::put('/assessments/{assessment}', [AdminAssessmentsController::class, 'update']);
            Route::delete('/assessments/{assessment}', [AdminAssessmentsController::class, 'destroy']);

            // Questions (if your builder uses it)
            Route::post('/assessments/{assessment}/questions', [AdminQuestionsController::class, 'store']);
            Route::put('/questions/{question}', [AdminQuestionsController::class, 'update']);
            Route::delete('/questions/{question}', [AdminQuestionsController::class, 'destroy']);

            // Reports
            Route::get('/reports/completions', [AdminReportsController::class, 'completions']);
            Route::get('/reports/assessments', [AdminReportsController::class, 'assessments']);
        });

        /*
        |--------------------------------------------------------------------------
        | LEARNER APIs
        |--------------------------------------------------------------------------
        */
        Route::prefix('learner')->middleware(['role:learner'])->group(function () {
            Route::get('/my-courses', [MyCoursesController::class, 'index']);

            Route::get('/player/{enrollment}', [CoursePlayerController::class, 'show']);
            Route::post('/progress/{enrollment}', [ProgressController::class, 'store']);

            Route::get('/certificates', [LearnerCertificatesController::class, 'index']);

            Route::get('/courses/{enrollment}/assessments', [CourseAssessmentsController::class, 'index']);
            Route::get('/assessments/{assessment}', [AssessmentAttemptController::class, 'show']);
            Route::post('/assessments/{assessment}/submit', [AssessmentAttemptController::class, 'submit']);
        });

    });
});

/*
|--------------------------------------------------------------------------
| Public certificate verification (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/verify/{code}', [VerifyCertificateController::class, 'show']);
