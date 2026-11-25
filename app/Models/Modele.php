<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modele extends Model
{
    protected $fillable = [
        'reference',
        'designation',
        'dimensions',
        'description',
        'prix_conseille',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix_conseille' => 'decimal:3',
            'actif' => 'boolean',
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
