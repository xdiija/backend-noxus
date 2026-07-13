<?php

namespace App\Http\Requests\FosterPlacement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateFosterPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Scope the FK check to the org so a tenant can't place another
            // org's animal.
            'animal_id' => [
                'required',
                'integer',
                Rule::exists('animals', 'id')->where('organization_id', auth()->user()?->organization_id),
            ],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'O animal é obrigatório.',
            'animal_id.exists' => 'O animal informado é inválido.',
            'entry_date.required' => 'A data de entrada é obrigatória.',
            'entry_date.date' => 'A data de entrada é inválida.',
            'entry_date.before_or_equal' => 'A data de entrada não pode ser futura.',
        ];
    }
}
