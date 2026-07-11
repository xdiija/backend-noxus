<?php

namespace App\Http\Requests\Breed;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateBreedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'species_id' => [
                'required',
                'integer',
                'exists:species,id',
            ],
            'name' => [
                'required',
                'min:2',
                'max:255',
                // Unique among the shared global breeds for the chosen species
                // (ADR-0008).
                Rule::unique('breeds')
                    ->whereNull('organization_id')
                    ->where('species_id', $this->species_id)
                    ->ignore($this->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'species_id.required' => 'A espécie é obrigatória.',
            'species_id.integer' => 'A espécie deve ser um número inteiro.',
            'species_id.exists' => 'A espécie informada não existe.',
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'name.unique' => 'Já existe uma raça com este nome para esta espécie.',
        ];
    }
}
