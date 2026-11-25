<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaiementCharge extends Model
{
    protected $table = 'paiements_charges';

    protected $fillable = [
        'charge_id',
        'date_paiement',
        'montant',
        'mode',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_paiement' => 'date',
            'montant' => 'decimal:3',
        ];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    public function mouvementCaisse(): HasOne
    {
        return $this->hasOne(MouvementCaisse::class);
    }
}
