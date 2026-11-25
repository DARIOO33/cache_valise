<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Charge extends Model
{
    /**
     * `montant` is deliberately absent: it is a MySQL generated column
     * (quantite * prix_unitaire) and must never be written by the application.
     */
    protected $fillable = [
        'numero_piece',
        'date_charge',
        'lot_id',
        'fournisseur_id',
        'categorie',
        'designation',
        'quantite',
        'unite',
        'prix_unitaire',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_charge' => 'date',
            'quantite' => 'decimal:3',
            'prix_unitaire' => 'decimal:3',
            'montant' => 'decimal:3',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementCharge::class);
    }
}
