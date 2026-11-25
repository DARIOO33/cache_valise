<?php

namespace App\Models;

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
}
