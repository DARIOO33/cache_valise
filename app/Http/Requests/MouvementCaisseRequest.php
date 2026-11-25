<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MouvementCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_mouvement' => ['required', 'date'],
            // reglement_client and paiement_charge are created by a payment, never by hand.
            'type' => ['required', 'in:solde_initial,alimentation,retrait,depot_bancaire,correction'],
            'sens' => ['required_if:type,correction', 'nullable', 'in:entree,sortie'],
            'montant' => ['required', 'numeric', 'min:0.001', 'max:99999999.999'],
            'libelle' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['date_mouvement' => 'date du mouvement', 'libelle' => 'libelle'];
    }
}
