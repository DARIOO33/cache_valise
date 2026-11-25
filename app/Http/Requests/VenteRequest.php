<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `numero_facture`, the totals and `statut` are never user input.
            'date_vente' => ['required', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'nom_comptoir' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date_vente' => 'date de vente',
            'client_id' => 'client',
            'nom_comptoir' => 'nom comptoir',
        ];
    }
}
