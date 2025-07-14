<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeadSubmissionController;
use App\Http\Controllers\DuplicateRecordController;

Route::get('/', function () {
    return Redirect::route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    /**
     * ==============================
     *        Lead Submission
     * ==============================
     */
    Route::prefix('lead_submission')->group(function () {
        Route::get('/', [LeadSubmissionController::class, 'index'])->name('lead_submission');
        Route::get('/getCoreLeads', [LeadSubmissionController::class, 'getCoreLeads'])->name('lead_submission.getCoreLeads');

        Route::post('/upload', [LeadSubmissionController::class, 'upload'])->name('lead_submission.upload');
    });

    Route::prefix('duplicate')->group(function () {
        Route::get('/getDuplicateRecords', [DuplicateRecordController::class, 'getDuplicateRecords'])->name('duplicate.getDuplicateRecords');
        Route::get('/getRecordsByDuplicateId', [DuplicateRecordController::class, 'getRecordsByDuplicateId'])->name('duplicate.getRecordsByDuplicateId');

    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
