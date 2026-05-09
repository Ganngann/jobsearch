<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JobOfferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $stats = [
        'total' => \App\Models\JobOffer::count(),
        'detailed' => \App\Models\JobOffer::where('is_detailed', true)->count(),
        'vectorized' => \App\Models\JobOffer::whereNotNull('vector_embedding')->count(),
        'active' => \App\Models\JobOffer::where('status', 'active')->count(),
    ];
    return view('welcome', compact('stats'));
});

Route::post('/feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');

Route::get('/welcome', function () {
    return view('onboarding', ['max_size' => ini_get('upload_max_filesize')]);
})->middleware(['auth'])->name('onboarding');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [JobOfferController::class, 'dashboard'])->name('dashboard');
    Route::get('/jobs/{jobOffer}/preview', [JobOfferController::class, 'preview'])->name('jobs.preview');
    Route::get('/employers/{employer}/logo', [JobOfferController::class, 'logo'])->name('employers.logo');
    Route::get('/jobs/{jobOffer}', [JobOfferController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{jobOffer}/match', [JobOfferController::class, 'match'])->name('jobs.match');
    Route::post('/jobs/{jobOffer}/refresh', [JobOfferController::class, 'refresh'])->name('jobs.refresh');

    // Discovery
    Route::get('/discovery', [\App\Http\Controllers\DiscoveryController::class, 'index'])->name('discovery.index');
    Route::get('/discovery/suggest', [\App\Http\Controllers\DiscoveryController::class, 'suggest'])->name('discovery.suggest');
    Route::post('/discovery/referentiel/{code}/status', [\App\Http\Controllers\DiscoveryController::class, 'setReferentielStatus'])->name('discovery.referentiel-status');
    Route::get('/discovery/children/{code}', [\App\Http\Controllers\DiscoveryController::class, 'children'])->name('discovery.children');
    Route::post('/discovery/metiers/{metier}/status', [\App\Http\Controllers\DiscoveryController::class, 'setMetierStatus'])->name('discovery.metier-status');

    // Vector Testing
    Route::post('/jobs/{jobOffer}/embed', [\App\Http\Controllers\VectorController::class, 'embedJob'])->name('jobs.embed');
    Route::post('/profile/embed', [\App\Http\Controllers\VectorController::class, 'embedProfile'])->name('profile.embed');
    Route::post('/matching/top-ai-sync', [JobOfferController::class, 'triggerTopAi'])->name('matching.top-ai-sync');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/languages', [ProfileController::class, 'updateLanguages'])->name('profile.languages.update');
    Route::get('/profile/mobility', [\App\Http\Controllers\MobilityController::class, 'index'])->name('profile.mobility.index');
    Route::patch('/profile/mobility', [\App\Http\Controllers\MobilityController::class, 'update'])->name('profile.mobility.update');
    Route::post('/profile/upload-resume', [ProfileController::class, 'uploadResume'])->name('profile.upload-resume');
    Route::get('/profile/skills', [\App\Http\Controllers\ProfileSkillController::class, 'index'])->name('profile.skills.index');
    Route::post('/profile/skills/suggest', [\App\Http\Controllers\ProfileSkillController::class, 'suggest'])->name('profile.skills.suggest');
    Route::post('/profile/skills/{skill}/status', [\App\Http\Controllers\ProfileSkillController::class, 'updateStatus'])->name('profile.skills.status');
    Route::get('/profile/skills/soft', [\App\Http\Controllers\ProfileSkillController::class, 'softSkills'])->name('profile.skills.soft');
    
    // Route de synchronisation (accessible sans 'verified')
    Route::match(['get', 'post'], '/vector-sync', [\App\Http\Controllers\VectorController::class, 'syncSimilarities'])->name('matching.vector-sync');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // AI Profile Builder
    Route::get('/profile/builder', [\App\Http\Controllers\ProfileChatController::class, 'index'])->name('profile.builder');
    Route::post('/profile/builder/message', [\App\Http\Controllers\ProfileChatController::class, 'sendMessage'])->name('profile.builder.message');
    Route::post('/profile/builder/sessions/{session}/archive', [\App\Http\Controllers\ProfileChatController::class, 'toggleArchive'])->name('profile.builder.sessions.archive');
    Route::post('/profile/builder/upload', [\App\Http\Controllers\ProfileChatController::class, 'uploadDocument'])->name('profile.builder.upload');
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

    Route::get('/api/metiers/search', [ProfileController::class, 'searchMetiers'])->name('api.metiers.search');
    Route::get('/api/skills/search', [ProfileController::class, 'searchSkills'])->name('api.skills.search');
});

require __DIR__.'/auth.php';

// Routes Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/users/{user}/toggle-admin', [\App\Http\Controllers\AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::post('/users/{user}/update-limit', [\App\Http\Controllers\AdminController::class, 'updateLimit'])->name('users.update-limit');
    Route::post('/matching/clear', [\App\Http\Controllers\AdminController::class, 'clearAllMatches'])->name('matching.clear');
    Route::post('/matching/clear-ai', [\App\Http\Controllers\AdminController::class, 'clearAiAnalyses'])->name('matching.clear-ai');
    Route::post('/matching/vector-sync', [\App\Http\Controllers\VectorController::class, 'syncGlobalSimilarities'])->name('matching.vector-sync');
    Route::post('/matching/scan', [\App\Http\Controllers\VectorController::class, 'launchBatchVectorization'])->name('matching.scan');
    Route::get('/matching/clear-get', [\App\Http\Controllers\AdminController::class, 'clearAllMatches'])->name('matching.clear-get');
    
    // Queue Monitoring
    Route::get('/queue', [\App\Http\Controllers\AdminController::class, 'queueMonitor'])->name('queue.monitor');
    Route::post('/queue/clear', [\App\Http\Controllers\AdminController::class, 'clearQueue'])->name('queue.clear');
    Route::post('/queue/failed/clear', [\App\Http\Controllers\AdminController::class, 'clearFailedJobs'])->name('queue.failed.clear');
    Route::delete('/queue/jobs/{id}', [\App\Http\Controllers\AdminController::class, 'deleteJob'])->name('queue.jobs.delete');
    Route::post('/queue/jobs/{id}/retry', [\App\Http\Controllers\AdminController::class, 'retryJob'])->name('queue.jobs.retry');
});
