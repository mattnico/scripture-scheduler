<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('plans.create');
});

// Public routes - no auth required
Route::get('/plans/{plan}/calendar.ics', [CalendarController::class, 'show'])->name('plans.calendar');
Route::get('/share/{token}', [PublicPlanController::class, 'show'])->name('plans.public');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/curricula', [CurriculumController::class, 'index'])->name('curricula.index');
    Route::get('/curricula/create', [CurriculumController::class, 'create'])->name('curricula.create');
    Route::get('/curricula/{curriculum}/edit', [CurriculumController::class, 'edit'])->name('curricula.edit');
    Route::delete('/curricula/{curriculum}', [CurriculumController::class, 'destroy'])->name('curricula.destroy');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
    
    Route::get('/plans/{plan}/export/csv', [ExportController::class, 'csv'])->name('plans.export.csv');
    Route::get('/plans/{plan}/export/table', [ExportController::class, 'table'])->name('plans.export.table');
    Route::get('/plans/{plan}/export/calendar', [ExportController::class, 'calendar'])->name('plans.export.calendar');
    Route::get('/plans/{plan}/export/pdf/table', [ExportController::class, 'pdfTable'])->name('plans.export.pdf.table');
    Route::get('/plans/{plan}/export/pdf/calendar', [ExportController::class, 'pdfCalendar'])->name('plans.export.pdf.calendar');

    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/join', [EnrollmentController::class, 'create'])->name('enrollments.create');
    Route::get('/enrollments/{enrollment}/create-plan', [EnrollmentController::class, 'createPlan'])->name('enrollments.create-plan');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
});

require __DIR__.'/auth.php';
