<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Application\ApplicationController;
use App\Http\Controllers\Dashboard\RPSChiefDashboardController; // if you have a controller

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');




Route::get('rps-chief-dashboard', function () {
    return Inertia::render('RPSChiefDashboard');
})->middleware(['auth', 'verified'])->name('rps.chief.dashboard');

Route::get('rps-staff-dashboard', function () {
    return Inertia::render('RPSStaffDashboard');
})->middleware(['auth', 'verified'])->name('rps.staff.dashboard');


Route::get('applications/index', function () {
    return Inertia::render('applications/index');
})->middleware(['auth', 'verified'])->name('applications.index');

Route::get('applications/pending_application', function () {
    return Inertia::render('applications/pending_application');
})->middleware(['auth', 'verified'])->name('applications.pending_application');

Route::prefix('applications')->group(function () {
    Route::get('{id}/view', [ApplicationController::class, 'view'])
        ->name('applications.view');

    Route::get('{id}/edit', [ApplicationController::class, 'edit'])
        ->name('applications.edit');
});


Route::get('/application/index/{id}', function ($id) {
    return Inertia::render('application/index', [
        'id' => $id,
    ]);
})->middleware(['auth', 'verified'])->name('application.index');


Route::get('/applications/{id}/edit', [ApplicationController::class, 'edit'])
    ->name('applications.edit');

Route::put(
    '/applications/{id}/update-applicant-data',
    [ApplicationController::class, 'updateIndividualApplicant']
)->name('applications.update.individual.data');

Route::post('/applications/updateStatus', [ApplicationController::class, 'updateStatus'])
    ->name('applications.updateStatus');



Route::post('/applications/return', [ApplicationController::class, 'returnApplication'])
    ->middleware(['auth', 'verified'])
    ->name('applications.return');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
