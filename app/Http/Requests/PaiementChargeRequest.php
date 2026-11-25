<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaiementChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_paiement' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:0.001', 'max:99999999.999'],
            'mode' => ['required', 'in:espece,virement,cheque'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['date_paiement' => 'date du paiement', 'mode' => 'mode de paiement'];
    }
}
