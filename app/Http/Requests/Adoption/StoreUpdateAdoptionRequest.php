<?php

namespace App\Http\Requests\Adoption;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateAdoptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = auth()->user()?->organization_id;

        return [
            // Scope the FK checks to the org so a tenant can't reference another
            // org's animal/adopter.
            'animal_id' => [
                'required',
                'integer',
                Rule::exists('animals', 'id')->where('organization_id', $orgId),
            ],
            'adopter_id' => [
                'required',
                'integer',
                Rule::exists('adopters', 'id')->where('organization_id', $orgId),
            ],
            'adoption_date' => ['required', 'date', 'before_or_equal:today'],
            'status_id' => ['nullable', 'integer', 'exists:adoption_statuses,id'],
            'agreement_signed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'O animal é obrigatório.',
            'animal_id.exists' => 'O animal informado é inválido.',
            'adopter_id.required' => 'O adotante é obrigatório.',
            'adopter_id.exists' => 'O adotante informado é inválido.',
            'adoption_date.required' => 'A data da adoção é obrigatória.',
            'adoption_date.date' => 'A data da adoção é inválida.',
            'adoption_date.before_or_equal' => 'A data da adoção não pode ser futura.',
            'status_id.exists' => 'O status informado é inválido.',
        ];
    }
}
