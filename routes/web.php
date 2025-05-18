<?php

use App\Http\Controllers\Api\LeagueController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/api/teams', [LeagueController::class, 'getTeams'])
    ->name('teams');

Route::get('/api/fixtures/{week?}', [LeagueController::class, 'getFixtures'])
    ->name('fixtures');

Route::get('/api/play-next-week', [LeagueController::class, 'playNextWeek'])
    ->name('play_next_week');

Route::get('/api/play-all-weeks', [LeagueController::class, 'playAllWeek'])
    ->name('play_all_week');

Route::get('/api/standings', [LeagueController::class, 'calculateStandings'])
    ->name('standings');

Route::get('/api/predictions', [LeagueController::class, 'calculatePredictions'])
    ->name('predictions');

Route::get('/api/reset', [LeagueController::class, 'resetData'])
    ->name('reset');


Route::get('/teams', function () {
    return Inertia::render('league/Teams');
})->name('teams');

Route::get('/fixtures', function () {
    return Inertia::render('league/Fixtures');
})->name('fixtures');

Route::get('/simulation', function () {
    return Inertia::render('league/Simulation');
})->name('simulation');


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
