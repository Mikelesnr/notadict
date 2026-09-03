<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentHubController;
use App\Http\Controllers\RecordingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Welcome / Landing page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Authenticated Application Routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Document Hub / Dashboard (Lists transcriptions)
    Route::get('/dashboard', [DocumentHubController::class, 'index'])->name('dashboard');
    Route::get('/documents', [DocumentHubController::class, 'index'])->name('documents.index');
    Route::get('/documents/{transcription}', [DocumentHubController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{transcription}', [DocumentHubController::class, 'destroy'])->name('documents.destroy');

    // Audio Recording & Chunking Workflow
    Route::get('/recorder', [RecordingController::class, 'create'])->name('recorder.index');
    Route::post('/recorder/chunk', [RecordingController::class, 'uploadChunk'])->name('recorder.chunk');
    Route::post('/recorder/finalize', [RecordingController::class, 'finalize'])->name('recorder.finalize');

    // Profile Management[cite: 4]
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
