<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // An unchecked checkbox is simply absent from the request.
        $this->merge(['actif' => $this->boolean('actif')]);
    }

    public function rules(): array
    {
        return [
            'reference' => [
                'required', 'string', 'max:50',
                Rule::unique('modeles', 'reference')->ignore($this->route('modele')),
            ],
            'designation' => ['required', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'prix_conseille' => ['required', 'numeric', 'min:0', 'max:99999999.999'],
            'actif' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reference' => 'reference',
            'designation' => 'designation',
            'prix_conseille' => 'prix conseille',
        ];
    }
}
