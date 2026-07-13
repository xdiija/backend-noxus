<?php

namespace App\Http\Requests\AnimalVaccine;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateAnimalVaccineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vaccine_id' => ['required', 'integer', 'exists:vaccines,id'],
            'application_date' => ['required', 'date'],
            // e.g. "1ª dose", "reforço".
            'dose' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'vaccine_id.required' => 'A vacina é obrigatória.',
            'vaccine_id.exists' => 'A vacina informada é inválida.',
            'application_date.required' => 'A data de aplicação é obrigatória.',
            'application_date.date' => 'A data de aplicação é inválida.',
        ];
    }
}
