<?php

use Illuminate\Support\Facades\Route;

// Controllers (admin)
use App\Http\Controllers\Api\Admin\UsersController;
use App\Http\Controllers\Api\Admin\CoursesController;
use App\Http\Controllers\Api\Admin\EnrollmentsController;
use App\Http\Controllers\Api\Admin\AssessmentsController;
use App\Http\Controllers\Api\Admin\ReportsController;
use App\Http\Controllers\Api\Admin\SettingsController;

// Controllers (learner)
use App\Http\Controllers\Api\Learner\MyCoursesController;
use App\Http\Controllers\Api\Learner\CoursePlayerController;
use App\Http\Controllers\Api\Learner\AssessmentPlayerController;
use App\Http\Controllers\Api\Learner\CertificatesController;

Route::prefix('api')
    ->middleware(['web','auth'])
    ->group(function () {

        Route::get('/me', fn() => auth()->user());

        Route::middleware(['role:admin'])->prefix('admin')->group(function () {
            // Users
            Route::get('/users', [UsersController::class, 'index']);
            Route::post('/users', [UsersController::class, 'store']);
            Route::put('/users/{user}', [UsersController::class, 'update']);
            Route::delete('/users/{user}', [UsersController::class, 'destroy']);

            // Courses / Builder
            Route::get('/courses', [CoursesController::class, 'index']);
            Route::post('/courses', [CoursesController::class, 'store']);
            Route::get('/courses/{course}', [CoursesController::class, 'show']);
            Route::put('/courses/{course}', [CoursesController::class, 'update']);
            Route::delete('/courses/{course}', [CoursesController::class, 'destroy']);

            Route::post('/courses/{course}/modules', [CoursesController::class, 'addModule']);
            Route::post('/modules/{module}/lessons', [CoursesController::class, 'addLesson']);
            Route::post('/lessons/{lesson}/attach-asset', [CoursesController::class, 'attachAsset']);
            Route::post('/assets/upload', [CoursesController::class, 'uploadAsset']);

            // Enrollments
            Route::get('/enrollments', [EnrollmentsController::class, 'index']);
            Route::post('/enrollments', [EnrollmentsController::class, 'store']);
            Route::put('/enrollments/{enrollment}', [EnrollmentsController::class, 'update']);
            Route::delete('/enrollments/{enrollment}', [EnrollmentsController::class, 'destroy']);

            // Assessments
            Route::get('/assessments', [AssessmentsController::class, 'index']);
            Route::post('/assessments', [AssessmentsController::class, 'store']);
            Route::get('/assessments/{assessment}', [AssessmentsController::class, 'show']);
            Route::put('/assessments/{assessment}', [AssessmentsController::class, 'update']);
            Route::delete('/assessments/{assessment}', [AssessmentsController::class, 'destroy']);

            // Reports
            Route::get('/reports/completions', [ReportsController::class, 'completions']);
            Route::get('/reports/assessments', [ReportsController::class, 'assessments']);

            // Settings
            Route::get('/settings', [SettingsController::class, 'show']);
            Route::put('/settings', [SettingsController::class, 'update']);
            Route::post('/settings/test-email', [SettingsController::class, 'testEmail']);
        });

        // Learner
        Route::prefix('learner')->group(function () {
            Route::get('/my-courses', [MyCoursesController::class, 'index']);
            Route::get('/course-player/{enrollment}', [CoursePlayerController::class, 'show']);
            Route::post('/course-player/{enrollment}/progress', [CoursePlayerController::class, 'updateProgress']);

            Route::get('/assessment/{enrollment}', [AssessmentPlayerController::class, 'show']);
            Route::post('/assessment/{enrollment}/submit', [AssessmentPlayerController::class, 'submit']);

            Route::get('/certificates', [CertificatesController::class, 'index']);
        });
    });
