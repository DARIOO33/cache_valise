<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneVente extends Model
{
    protected $table = 'lignes_vente';

    protected $fillable = [
        'vente_id',
        'lot_id',
        'designation',
        'quantite',
        'prix_unitaire',
        'remise_pourcent',
        'remise_montant',
        'montant_ligne',
        'cout_unitaire',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'decimal:3',
            'remise_pourcent' => 'decimal:2',
            'remise_montant' => 'decimal:3',
            'montant_ligne' => 'decimal:3',
            'cout_unitaire' => 'decimal:3',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
