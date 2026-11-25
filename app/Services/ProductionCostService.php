<?php

namespace App\Services;

use App\Models\Lot;
use Illuminate\Support\Collection;

/**
 * Cost of revient per lot (section 3.2).
 *
 * Nothing here is stored. A lot's unit cost is always recomputed from its
 * charges, precisely because charges may be corrected later; that is why a
 * sale line freezes its own copy at validation (section 3.4).
 */
class ProductionCostService
{
    public function coutTotal(Lot $lot): float
    {
        if (array_key_exists('cout_total', $lot->getAttributes())) {
            return (float) $lot->getAttributes()['cout_total'];
        }

        return (float) $lot->charges()->sum('montant');
    }

    public function coutUnitaire(Lot $lot): float
    {
        if ($lot->quantite_produite < 1) {
            return 0.0;
        }

        return round($this->coutTotal($lot) / $lot->quantite_produite, 3);
    }

    /** @return Collection<string, float> montant per categorie, biggest first */
    public function repartitionParCategorie(Lot $lot): Collection
    {
        return $lot->charges()
            ->selectRaw('categorie, SUM(montant) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie')
            ->map(fn ($total) => (float) $total)
            ->sortDesc();
    }
}
