<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountStoreUpdateRequest extends FormRequest
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
                Rule::unique('accounts')->ignore($this->id)
            ],
            'type' => [
                'required',
                Rule::in(['bank', 'cash', 'credit_card'])
            ],
            'balance' => [
                'nullable',
                'numeric',
                'min:0'
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
            'name.unique' => 'O nome já está em uso.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser um dos seguintes valores: bank, cash ou credit_card.',
            'balance.numeric' => 'O campo saldo deve ser um número.',
            'balance.min' => 'O campo saldo não pode ser negativo.',
            'status.required' => 'O campo status é obrigatório.',
            'status.boolean' => 'O campo status deve ser verdadeiro (1) ou falso (0).',
        ];
    }
}