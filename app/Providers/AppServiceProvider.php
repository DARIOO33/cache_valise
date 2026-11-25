<?php

namespace App\Providers;

use App\Models\Charge;
use App\Models\LigneVente;
use App\Models\Lot;
use App\Models\MouvementCaisse;
use App\Models\PaiementCharge;
use App\Models\Reglement;
use App\Models\Vente;
use App\Observers\AuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** The records that carry money, and whose history section 4 requires. */
    private const AUDITABLES = [
        Lot::class,
        Charge::class,
        PaiementCharge::class,
        Vente::class,
        LigneVente::class,
        Reglement::class,
        MouvementCaisse::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The interface is Bootstrap 5 (section 5), not Tailwind.
        Paginator::useBootstrapFive();

        foreach (self::AUDITABLES as $modele) {
            $modele::observe(AuditObserver::class);
        }
    }
}
