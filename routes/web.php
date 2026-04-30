<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JobOfferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [JobOfferController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/jobs/{jobOffer}', [JobOfferController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('jobs.show');

Route::post('/jobs/{jobOffer}/match', [JobOfferController::class, 'match'])
    ->middleware(['auth', 'verified'])
    ->name('jobs.match');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
