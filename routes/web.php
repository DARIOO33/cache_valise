<?php

use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\LigneVenteController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\ModeleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VenteController;
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

    // Phase 3 - suppliers and manufacturing charges.
    Route::resource('fournisseurs', FournisseurController::class);
    Route::resource('charges', ChargeController::class)->except(['show']);

    // Phase 4 - customers, invoices and their lines.
    Route::resource('clients', ClientController::class);
    // A vente is never deleted: it is cancelled, so the number series has no gap.
    Route::resource('ventes', VenteController::class)->except(['destroy']);
    Route::post('ventes/{vente}/validation', [VenteController::class, 'validation'])->name('ventes.validation');
    Route::post('ventes/{vente}/annulation', [VenteController::class, 'annulation'])->name('ventes.annulation');
    Route::post('ventes/{vente}/lignes', [LigneVenteController::class, 'store'])->name('ventes.lignes.store');
    Route::delete('ventes/{vente}/lignes/{ligne}', [LigneVenteController::class, 'destroy'])->name('ventes.lignes.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
