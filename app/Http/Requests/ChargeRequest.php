<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `numero_piece` is never user input: SequenceService allocates it.
            // `montant` is never user input either: MySQL generates it.
            'date_charge' => ['required', 'date'],
            'lot_id' => ['required', 'exists:lots,id'],
            'fournisseur_id' => ['nullable', 'exists:fournisseurs,id'],
            'categorie' => ['required', 'in:tissu,couture,impression,autre'],
            'designation' => ['required', 'string', 'max:255'],
            'quantite' => ['required', 'numeric', 'min:0.001', 'max:9999999'],
            'unite' => ['nullable', 'string', 'max:50'],
            'prix_unitaire' => ['required', 'numeric', 'min:0', 'max:99999999.999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date_charge' => 'date de charge',
            'lot_id' => 'lot',
            'fournisseur_id' => 'fournisseur',
            'prix_unitaire' => 'prix unitaire',
        ];
    }
}
