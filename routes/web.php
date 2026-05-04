<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\ForemSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $stats = [
        'jobs' => \App\Models\JobOffer::where('status', 'active')->count(),
        'metiers' => \App\Models\Metier::whereHas('jobOffers')->count(),
    ];
    return view('welcome', compact('stats'));
});

Route::post('/feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [JobOfferController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [ForemSearchController::class, 'index'])->name('forem.search');
    Route::get('/jobs/{jobOffer}/preview', [JobOfferController::class, 'preview'])->name('jobs.preview');
    Route::get('/employers/{employer}/logo', [JobOfferController::class, 'logo'])->name('employers.logo');
    Route::get('/jobs/{jobOffer}', [JobOfferController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{jobOffer}/match', [JobOfferController::class, 'match'])->name('jobs.match');
    Route::post('/jobs/{jobOffer}/refresh', [JobOfferController::class, 'refresh'])->name('jobs.refresh');

    // Discovery
    Route::get('/discovery', [\App\Http\Controllers\DiscoveryController::class, 'index'])->name('discovery.index');
    Route::get('/discovery/suggest', [\App\Http\Controllers\DiscoveryController::class, 'suggest'])->name('discovery.suggest');
    Route::post('/discovery/favorite/{referentiel}', [\App\Http\Controllers\DiscoveryController::class, 'toggleFavorite'])->name('discovery.favorite');
    Route::post('/discovery/blacklist/{referentiel}', [\App\Http\Controllers\DiscoveryController::class, 'toggleBlacklist'])->name('discovery.blacklist');
    Route::get('/discovery/children/{code}', [\App\Http\Controllers\DiscoveryController::class, 'children'])->name('discovery.children');
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
    Route::post('/profile/builder/experience/{id}/accept', [\App\Http\Controllers\ProfileChatController::class, 'acceptExperience'])->name('profile.builder.experience.accept');
    Route::post('/profile/builder/education/{id}/accept', [\App\Http\Controllers\ProfileChatController::class, 'acceptEducation'])->name('profile.builder.education.accept');
    Route::post('/profile/builder/item/{type}/{id}/accept', [\App\Http\Controllers\ProfileChatController::class, 'acceptItem'])->name('profile.builder.item.accept');
    Route::post('/profile/builder/item/{type}/{id}/reject', [\App\Http\Controllers\ProfileChatController::class, 'rejectItem'])->name('profile.builder.item.reject');
    Route::delete('/profile/builder/item/{type}/{id}', [\App\Http\Controllers\ProfileChatController::class, 'deleteItem'])->name('profile.builder.item.delete');
    Route::patch('/profile/builder/item/{type}/{id}', [\App\Http\Controllers\ProfileChatController::class, 'updateItem'])->name('profile.builder.item.update');
    Route::post('/profile/builder/item/{type}', [\App\Http\Controllers\ProfileChatController::class, 'storeItem'])->name('profile.builder.item.store');
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
    // User Journey (Experiences & Education)
    Route::get('/profile/journey', [\App\Http\Controllers\UserJourneyController::class, 'index'])->name('profile.journey');
    Route::post('/profile/journey/experience', [\App\Http\Controllers\UserJourneyController::class, 'storeExperience'])->name('profile.journey.experience.store');
    Route::delete('/profile/journey/experience/{experience}', [\App\Http\Controllers\UserJourneyController::class, 'deleteExperience'])->name('profile.journey.experience.delete');
    Route::post('/profile/journey/education', [\App\Http\Controllers\UserJourneyController::class, 'storeEducation'])->name('profile.journey.education.store');
    Route::delete('/profile/journey/education/{education}', [\App\Http\Controllers\UserJourneyController::class, 'deleteEducation'])->name('profile.journey.education.delete');

    Route::post('/profile/journey/experience/{experience}/validate', [\App\Http\Controllers\UserJourneyController::class, 'validateExperience'])->name('profile.journey.experience.validate');
    Route::post('/profile/journey/education/{education}/validate', [\App\Http\Controllers\UserJourneyController::class, 'validateEducation'])->name('profile.journey.education.validate');

    Route::get('/api/metiers/search', [ProfileController::class, 'searchMetiers'])->name('api.metiers.search');
    Route::get('/api/skills/search', [ProfileController::class, 'searchSkills'])->name('api.skills.search');
});

require __DIR__.'/auth.php';
