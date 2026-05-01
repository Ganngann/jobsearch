<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\ForemSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [JobOfferController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [ForemSearchController::class, 'index'])->name('forem.search');
    Route::get('/jobs/{jobOffer}', [JobOfferController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{jobOffer}/match', [JobOfferController::class, 'match'])->name('jobs.match');
    Route::post('/jobs/{jobOffer}/refresh', [JobOfferController::class, 'refresh'])->name('jobs.refresh');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/skills', [ProfileController::class, 'updateSkills'])->name('profile.skills.update');
    Route::patch('/profile/languages', [ProfileController::class, 'updateLanguages'])->name('profile.languages.update');
    Route::patch('/profile/permits', [ProfileController::class, 'updatePermits'])->name('profile.permits.update');
    Route::patch('/profile/mobility', [ProfileController::class, 'updateMobility'])->name('profile.mobility.update');
    Route::post('/profile/analyze', [ProfileController::class, 'analyze'])->name('profile.analyze');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
