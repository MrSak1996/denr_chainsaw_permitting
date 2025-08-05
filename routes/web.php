<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('applications/index', function () {
    return Inertia::render('applications/index');
})->middleware(['auth', 'verified'])->name('applications.index');

Route::get('applications/pending_application', function () {
    return Inertia::render('applications/pending_application');
})->middleware(['auth', 'verified'])->name('applications.pending_application');


Route::get('/application/index/{id}', function ($id) {
    return Inertia::render('application/index', [
        'id' => $id,
    ]);
})->middleware(['auth', 'verified'])->name('application.index');



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
