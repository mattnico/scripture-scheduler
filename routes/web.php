<?php

use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
});

require __DIR__.'/auth.php';
