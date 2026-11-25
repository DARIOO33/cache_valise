<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReglementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_reglement' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:0.001', 'max:99999999.999'],
            'mode' => ['required', 'in:espece,virement,cheque'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['date_reglement' => 'date du reglement', 'mode' => 'mode de reglement'];
    }
}
