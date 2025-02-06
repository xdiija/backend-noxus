<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionCategoryStoreUpdateRequest extends FormRequest
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
                'min:3',
                'max:255',
                Rule::unique('transaction_categories')->ignore($this->id)
            ],
            'type' => [
                'required',
                Rule::in(['income', 'expense'])
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:transaction_categories,id'
            ],
            'status' => [
                'required',
                'boolean'
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
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser um dos seguintes valores: income ou expense.',
            'parent_id.integer' => 'O campo categoria pai deve ser um número inteiro.',
            'parent_id.exists' => 'A categoria pai selecionada é inválida.',
            'status.required' => 'O campo status é obrigatório.',
            'status.boolean' => 'O campo status deve ser verdadeiro (1) ou falso (0).',
        ];
    }
}