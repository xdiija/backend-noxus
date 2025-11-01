<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostCenterStoreUpdateRequest extends FormRequest
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
                Rule::unique('cost_centers')->ignore($this->id),
            ],
            'status' => [
                'required',
                'integer',
                'in:1,2',   
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'name.unique' => 'O nome já está em uso.',
            'status.required' => 'O campo status é obrigatório.',
            'status.boolean' => 'O campo status deve ser verdadeiro (1) ou falso (0).',
        ];
    }
}
