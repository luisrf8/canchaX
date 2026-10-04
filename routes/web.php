<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\RealtimeController;
use Illuminate\Support\Facades\Route;

// Real-Time WebSockets SSE Stream & Notifications Endpoint
Route::get('/api/realtime/stream', [RealtimeController::class, 'stream'])->name('realtime.stream');
Route::get('/api/notifications', [RealtimeController::class, 'getNotifications'])->name('notifications.index');
Route::post('/api/notifications/read-all', [RealtimeController::class, 'markNotificationsRead'])->name('notifications.readAll');
Route::post('/api/subscriptions/toggle', [RealtimeController::class, 'toggleSubscription'])->name('subscriptions.toggle');

// Public Landing Page & Community Explorer
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/organizers/{id}', [LandingController::class, 'showOrganizer'])->name('organizers.show');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Read-Only Views (Guests & Logged In)
Route::get('/dashboard', [TournamentController::class, 'index'])->name('dashboard');
Route::get('/tournaments/switch/{id}', [TournamentController::class, 'switchTournament'])->name('tournaments.switch');
Route::get('/tournaments/bracket/{id?}', [TournamentController::class, 'bracket'])->name('tournaments.bracket');
Route::get('/tournaments/standings/{id?}', [TournamentController::class, 'standings'])->name('tournaments.standings');
Route::get('/matches/{tournamentId?}', [MatchController::class, 'index'])->name('matches.index');
Route::get('/matches/live/{id}', [MatchController::class, 'liveConsole'])->name('matches.live');
Route::get('/teams/{tournamentId?}', [TeamController::class, 'index'])->name('teams.index');

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    Route::get('/tournaments/wizard', [TournamentController::class, 'wizard'])->name('tournaments.wizard');
    Route::post('/tournaments/wizard', [TournamentController::class, 'storeWizard'])->name('tournaments.storeWizard');
    Route::delete('/tournaments/{id}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');

    // Collaborators & Sub-users Management
    Route::post('/tournaments/{id}/collaborators', [CollaboratorController::class, 'store'])->name('collaborators.store');
    Route::delete('/tournaments/{id}/collaborators/{userId}', [CollaboratorController::class, 'destroy'])->name('collaborators.destroy');

    // Live Match Control Actions
    Route::post('/matches/live/{id}/score', [MatchController::class, 'updateScore'])->name('matches.score');
    Route::post('/matches/live/{id}/card', [MatchController::class, 'addCard'])->name('matches.card');
    Route::post('/matches/live/{id}/stats', [MatchController::class, 'updateStats'])->name('matches.stats');
    Route::post('/matches/live/{id}/timer', [MatchController::class, 'updateTimer'])->name('matches.timer');
    Route::delete('/matches/live/{id}/incident/{incidentId}', [MatchController::class, 'deleteIncident'])->name('matches.deleteIncident');
    Route::post('/matches/live/{id}/finish', [MatchController::class, 'finishMatch'])->name('matches.finish');

    // Teams & Players Management
    Route::post('/teams', [TeamController::class, 'storeTeam'])->name('teams.store');
    Route::post('/players', [TeamController::class, 'storePlayer'])->name('players.store');
    Route::delete('/teams/{id}', [TeamController::class, 'destroyTeam'])->name('teams.destroy');
    Route::delete('/players/{id}', [TeamController::class, 'destroyPlayer'])->name('players.destroy');
});
