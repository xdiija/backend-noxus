<?php

namespace App\Http\Requests\Breed;

use Illuminate\Foundation\Http\FormRequest;

class IndexBreedsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'species_id' => ['sometimes', 'nullable', 'integer', 'exists:species,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'species_id.integer' => 'A espécie deve ser um número inteiro.',
            'species_id.exists' => 'A espécie informada não existe.',
        ];
    }
}
