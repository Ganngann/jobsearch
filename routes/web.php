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
    Route::get('/jobs/{jobOffer}/preview', [JobOfferController::class, 'preview'])->name('jobs.preview');
    Route::get('/employers/{employer}/logo', [JobOfferController::class, 'logo'])->name('employers.logo');
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
    Route::patch('/profile/metiers', [ProfileController::class, 'updateMetiers'])->name('profile.metiers.update');
    Route::patch('/profile/mobility', [ProfileController::class, 'updateMobility'])->name('profile.mobility.update');
    Route::post('/profile/magic-fill', [ProfileController::class, 'magicFill'])->name('profile.magic-fill');
    Route::post('/profile/analyze', [ProfileController::class, 'analyze'])->name('profile.analyze');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // AI Profile Builder
    Route::get('/profile/builder', [\App\Http\Controllers\ProfileChatController::class, 'index'])->name('profile.builder');
    Route::post('/profile/builder/message', [\App\Http\Controllers\ProfileChatController::class, 'sendMessage'])->name('profile.builder.message');
    Route::post('/profile/builder/sessions/{session}/archive', [\App\Http\Controllers\ProfileChatController::class, 'toggleArchive'])->name('profile.builder.sessions.archive');
    Route::post('/profile/builder/sync-skills', [\App\Http\Controllers\ProfileChatController::class, 'syncSkills'])->name('profile.builder.sync-skills');
    Route::get('/profile/builder/reset', [\App\Http\Controllers\ProfileChatController::class, 'resetSession'])->name('profile.builder.reset');
    Route::patch('/profile/builder/facts/{fact}', [\App\Http\Controllers\ProfileChatController::class, 'updateFact'])->name('profile.builder.facts.update');
    Route::post('/profile/builder/facts/{fact}/validate', [\App\Http\Controllers\ProfileChatController::class, 'validateFact'])->name('profile.builder.facts.validate');
    Route::post('/profile/builder/facts/{fact}/accept', [\App\Http\Controllers\ProfileChatController::class, 'acceptProposal'])->name('profile.builder.facts.accept');
    Route::post('/profile/builder/facts/{fact}/reject', [\App\Http\Controllers\ProfileChatController::class, 'rejectProposal'])->name('profile.builder.facts.reject');
    Route::delete('/profile/builder/facts/{fact}', [\App\Http\Controllers\ProfileChatController::class, 'deleteFact'])->name('profile.builder.facts.delete');
    Route::delete('/profile/facts/{fact}/skills/{skill}', [ProfileController::class, 'detachSkillFromFact'])->name('profile.facts.skills.detach');
    Route::post('/profile/skills/{skill}/add', [ProfileController::class, 'addSkill'])->name('profile.skills.add');
    Route::post('/profile/skills/{skill}/remove', [ProfileController::class, 'removeSkill'])->name('profile.skills.remove');
    Route::post('/profile/metiers/{metier}/add', [ProfileController::class, 'addMetier'])->name('profile.metiers.add');
    Route::post('/profile/metiers/{metier}/remove', [ProfileController::class, 'removeMetier'])->name('profile.metiers.remove');
    Route::post('/profile/skills/{skill}/blacklist', [ProfileController::class, 'blacklistSkill'])->name('profile.skills.blacklist');
    Route::delete('/profile/skills/{skill}/blacklist', [ProfileController::class, 'unblacklistSkill'])->name('profile.skills.unblacklist');
    Route::post('/profile/metiers/{metier}/blacklist', [ProfileController::class, 'blacklistMetier'])->name('profile.metiers.blacklist');
    Route::delete('/profile/metiers/{metier}/blacklist', [ProfileController::class, 'unblacklistMetier'])->name('profile.metiers.unblacklist');
    Route::get('/api/metiers/search', [ProfileController::class, 'searchMetiers'])->name('api.metiers.search');
});

require __DIR__.'/auth.php';
