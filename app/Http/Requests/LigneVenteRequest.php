<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LigneVenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A cancelled lot does not exist; an unfinished one only warns.
            'lot_id' => ['required', Rule::exists('lots', 'id')->whereNot('etat', 'annule')],
            'designation' => ['nullable', 'string', 'max:255'],
            'quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'prix_unitaire' => ['required', 'numeric', 'min:0', 'max:99999999.999'],
            'remise_pourcent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'lot_id' => 'lot',
            'prix_unitaire' => 'prix unitaire',
            'remise_pourcent' => 'remise',
        ];
    }
}
