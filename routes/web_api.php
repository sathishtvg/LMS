<?php

use Illuminate\Support\Facades\Route;

// ===== ADMIN CONTROLLERS =====
use App\Http\Controllers\Api\Admin\{
    CoursesController,
    EnrollmentsController,
    UsersController,
    SettingsController,
    CertificatesController,
    AssessmentsController,
    QuestionsController,
    ReportsController,
    CourseBuilderController
};

// ===== LEARNER CONTROLLERS =====
use App\Http\Controllers\Api\Learner\{
    MyCoursesController,
    CoursePlayerController,
    ProgressController,
    CertificatesController as LearnerCertificatesController,
    AssessmentAttemptController
};

Route::middleware(['web', 'auth'])->group(function () {

    Route::prefix('api')->group(function () {

        // ================= TEST =================
        Route::get('/__webapi_loaded', fn () => response()->json(['ok' => true]));

        // ================= ADMIN =================
        Route::prefix('admin')->middleware('role:admin')->group(function () {

            // ---- COURSES ----
            Route::apiResource('courses', CoursesController::class);
            Route::get('courses/{course}/full', [CoursesController::class, 'full']);

            // ---- COURSE BUILDER ----
            Route::post('courses/{course}/modules', [CourseBuilderController::class, 'createModule']);
            Route::put('modules/{module}', [CourseBuilderController::class, 'updateModule']);
            Route::delete('modules/{module}', [CourseBuilderController::class, 'deleteModule']);

            Route::post('modules/{module}/lessons', [CourseBuilderController::class, 'createLesson']);
            Route::put('lessons/{lesson}', [CourseBuilderController::class, 'updateLesson']);
            Route::delete('lessons/{lesson}', [CourseBuilderController::class, 'deleteLesson']);

            Route::post('courses/{course}/reorder', [CourseBuilderController::class, 'reorder']);

            Route::post('lessons/{lesson}/assets/upload', [CourseBuilderController::class, 'uploadAsset']);
            Route::post('lessons/{lesson}/assets/attach', [CourseBuilderController::class, 'attachAsset']);
            Route::delete('assets/{asset}', [CourseBuilderController::class, 'deleteAsset']);

            // ---- USERS ----
            Route::apiResource('users', UsersController::class)->only(['index', 'store', 'update', 'destroy']);

            // ---- ENROLLMENTS ----
            Route::apiResource('enrollments', EnrollmentsController::class)->only(['index', 'store', 'update', 'destroy']);

            // ---- SETTINGS ----
            Route::get('settings', [SettingsController::class, 'show']);
            Route::put('settings', [SettingsController::class, 'update']);

            // ---- ASSESSMENTS ----
            Route::apiResource('assessments', AssessmentsController::class);
            Route::post('assessments/{assessment}/questions', [QuestionsController::class, 'store']);
            Route::put('questions/{question}', [QuestionsController::class, 'update']);
            Route::delete('questions/{question}', [QuestionsController::class, 'destroy']);

            // ---- REPORTS ----
            Route::get('reports/completions', [ReportsController::class, 'completions']);
            Route::get('reports/assessments', [ReportsController::class, 'assessments']);
        });

        // ================= LEARNER =================
        Route::prefix('learner')->middleware('role:learner')->group(function () {
            Route::get('my-courses', [MyCoursesController::class, 'index']);
            Route::get('player/{enrollment}', [CoursePlayerController::class, 'show']);
            Route::post('progress/{enrollment}', [ProgressController::class, 'store']);
            Route::get('certificates', [LearnerCertificatesController::class, 'index']);
            Route::get('assessments/{assessment}', [AssessmentAttemptController::class, 'show']);
            Route::post('assessments/{assessment}/submit', [AssessmentAttemptController::class, 'submit']);
        });
    });
});
