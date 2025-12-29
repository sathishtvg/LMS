<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Learner\LearnerDashboardController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])
    ->middleware('throttle:30,1')
    ->name('login.submit');

Route::post('/logout', [AuthWebController::class, 'logout'])
    ->middleware(['auth'])
    ->name('logout');

Route::get('/__webphp_loaded', fn() => 'OK web.php loaded');    

Route::middleware(['auth','tenant'])->group(function () {

    Route::get('/app', fn () => inertia('AppShell'))->name('app');

    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/users', fn() => inertia('Admin/Users/Index'))->name('admin.users');
        Route::get('/settings', fn() => inertia('Admin/Settings/Index'))->name('admin.settings');
        Route::get('/courses', fn() => inertia('Admin/Courses/Index'))->name('admin.courses');
        Route::get('/enrollments', fn() => inertia('Admin/Enrollments/Index'))->name('admin.enrollments');
        Route::get('/certificates', fn() => inertia('Admin/Certificates/Index'))->name('admin.certificates');
        Route::get('/assessments', fn() => inertia('Admin/Assessments/Index'))->name('admin.assessments');
        Route::get('/assessments/{assessmentId}', fn($assessmentId) =>
            inertia('Admin/Assessments/Builder', ['assessmentId' => (int)$assessmentId])
        )->name('admin.assessments.builder');

        Route::get('/reports/completions', fn() => inertia('Admin/Reports/Completions'))->name('admin.reports.completions');
        Route::get('/reports/assessments', fn() => inertia('Admin/Reports/Assessments'))->name('admin.reports.assessments');

        Route::get('/courses/{courseId}/builder', fn($courseId) =>
            inertia('Admin/Courses/Builder', ['courseId' => (int)$courseId])
        )->name('admin.course_builder');
    });

    Route::prefix('learner')->middleware(['role:learner'])->group(function () {
        Route::get('/dashboard', [LearnerDashboardController::class, 'index'])->name('learner.dashboard');
        Route::get('/my-courses', fn() => inertia('Learner/MyCourses'))->name('learner.my_courses');
        Route::get('/certificates', fn() => inertia('Learner/Certificates'))->name('learner.certificates');
        Route::get('/player/{enrollmentId}', fn($enrollmentId) =>
            inertia('Learner/CoursePlayer', ['enrollmentId' => (int)$enrollmentId])
        )->name('learner.player');
        Route::get('/assessment/{assessmentId}/{enrollmentId}', fn($assessmentId,$enrollmentId) =>
            inertia('Learner/Assessment', ['assessmentId' => (int)$assessmentId, 'enrollmentId' => (int)$enrollmentId])
        )->name('learner.assessment');
    });
});

// Session-authenticated JSON APIs for Inertia
require __DIR__.'/web_api.php';
