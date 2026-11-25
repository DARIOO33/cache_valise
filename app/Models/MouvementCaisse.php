<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MouvementCaisse extends Model
{
    protected $table = 'mouvements_caisse';

    protected $fillable = [
        'date_mouvement',
        'type',
        'sens',
        'montant',
        'libelle',
        'reference',
        'reglement_id',
        'paiement_charge_id',
        'annule_mouvement_id',
    ];

    protected function casts(): array
    {
        return [
            'date_mouvement' => 'date',
            'montant' => 'decimal:3',
        ];
    }

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(Reglement::class);
    }

    public function paiementCharge(): BelongsTo
    {
        return $this->belongsTo(PaiementCharge::class);
    }

    /** The movement this one reverses. */
    public function mouvementAnnule(): BelongsTo
    {
        return $this->belongsTo(MouvementCaisse::class, 'annule_mouvement_id');
    }

    /** The contre-ecriture that reverses this movement, if any. */
    public function contreEcriture(): HasOne
    {
        return $this->hasOne(MouvementCaisse::class, 'annule_mouvement_id');
    }
}
