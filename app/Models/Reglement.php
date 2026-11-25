<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reglement extends Model
{
    protected $fillable = [
        'vente_id',
        'date_reglement',
        'montant',
        'mode',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_reglement' => 'date',
            'montant' => 'decimal:3',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function mouvementCaisse(): HasOne
    {
        return $this->hasOne(MouvementCaisse::class);
    }
}
