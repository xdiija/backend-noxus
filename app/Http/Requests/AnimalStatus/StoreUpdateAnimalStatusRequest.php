<?php

namespace App\Http\Requests\AnimalStatus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateAnimalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:2',
                'max:255',
                // Unique among the shared global statuses (ADR-0008).
                Rule::unique('animal_statuses')
                    ->whereNull('organization_id')
                    ->whereNull('deleted_at')
                    ->ignore($this->id),
            ],
            'color' => [
                'nullable',
                'string',
                'max:20',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'name.unique' => 'Já existe um status com este nome.',
            'color.max' => 'A cor não pode ter mais de 20 caracteres.',
            'sort_order.integer' => 'A ordem deve ser um número inteiro.',
            'sort_order.min' => 'A ordem não pode ser negativa.',
            'is_default.boolean' => 'O campo padrão deve ser verdadeiro ou falso.',
        ];
    }
}
