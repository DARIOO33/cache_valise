<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `numero` is never user input: SequenceService allocates it.
            'modele_id' => ['required', 'exists:modeles,id'],
            'quantite_produite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'date_production' => ['required', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'etat' => ['required', 'in:en_preparation,termine,annule'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'modele_id' => 'modele',
            'quantite_produite' => 'quantite produite',
            'date_production' => 'date de production',
            'date_debut' => 'date de debut',
            'date_fin' => 'date de fin',
        ];
    }
}
