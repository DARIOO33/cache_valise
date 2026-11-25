<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vente extends Model
{
    protected $fillable = [
        'numero_facture',
        'date_vente',
        'client_id',
        'nom_comptoir',
        'statut',
        'validated_at',
        'sous_total',
        'total_remise',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_vente' => 'date',
            'validated_at' => 'datetime',
            'sous_total' => 'decimal:3',
            'total_remise' => 'decimal:3',
            'total' => 'decimal:3',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(Reglement::class);
    }
}
