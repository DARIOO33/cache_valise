<?php

use App\Http\Controllers\LotController;
use App\Http\Controllers\ModeleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// The application has no public page: everything sits behind authentication.
Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Phase 2 - models and production lots.
    Route::resource('modeles', ModeleController::class)->except(['show', 'destroy']);
    Route::patch('modeles/{modele}/activation', [ModeleController::class, 'activation'])
        ->name('modeles.activation');

    // A lot is never deleted: it is set to `annule` instead.
    Route::resource('lots', LotController::class)->except(['destroy']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
