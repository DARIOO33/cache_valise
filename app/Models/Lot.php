<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = [
        'numero',
        'modele_id',
        'quantite_produite',
        'date_production',
        'date_debut',
        'date_fin',
        'etat',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'date_production' => 'date',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'quantite_produite' => 'integer',
        ];
    }

    public function modele(): BelongsTo
    {
        return $this->belongsTo(Modele::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    /**
     * Eager-loads `quantite_vendue` on a listing, so the accessor below
     * does not fire one query per row.
     */
    public function scopeWithQuantiteVendue(Builder $query): Builder
    {
        return $query->withSum(
            ['lignes as quantite_vendue' => fn (Builder $ligne) => $ligne->whereHas(
                'vente',
                fn (Builder $vente) => $vente->where('statut', 'validee')
            )],
            'quantite'
        );
    }

    /** Only validated sales count: a draft invoice must not look like stock sold. */
    public function getQuantiteVendueAttribute(): int
    {
        if (array_key_exists('quantite_vendue', $this->attributes)) {
            return (int) $this->attributes['quantite_vendue'];
        }

        return (int) $this->lignes()
            ->whereHas('vente', fn (Builder $vente) => $vente->where('statut', 'validee'))
            ->sum('quantite');
    }

    /** Informative only: section 3.2 forbids blocking a sale on stock. */
    public function getQuantiteRestanteAttribute(): int
    {
        return $this->quantite_produite - $this->quantite_vendue;
    }
}
