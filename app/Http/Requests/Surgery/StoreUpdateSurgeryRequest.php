<?php

namespace App\Http\Requests\Surgery;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateSurgeryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'surgery_date' => ['required', 'date'],
            'procedure' => ['required', 'string', 'max:255'],
            'veterinarian' => ['nullable', 'string', 'max:255'],
            'clinic' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            // Not persisted on the surgery: when true the Service marks the
            // animal as neutered (health.md invariant).
            'is_castration' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'surgery_date.required' => 'A data da cirurgia é obrigatória.',
            'surgery_date.date' => 'A data da cirurgia é inválida.',
            'procedure.required' => 'O procedimento é obrigatório.',
            'procedure.max' => 'O procedimento não pode exceder 255 caracteres.',
            'cost.numeric' => 'O custo deve ser um valor numérico.',
            'cost.min' => 'O custo não pode ser negativo.',
        ];
    }
}
