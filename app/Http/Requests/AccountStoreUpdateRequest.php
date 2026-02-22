<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\PhoneRule;
use App\Helpers\PhoneHelper;

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
            'bank_id' => [
                'nullable',
                'exists:banks,id',
                Rule::requiredIf($this->type === 'bank'),
            ],
            'agency' => [
                'nullable',
                'min:3',
                'max:15',
            ],
            'number' => [
                'nullable',
                'min:3',
                'max:20',
            ],
            'phone' => [
                'nullable',
                new PhoneRule
            ],
             'is_default' => [
                'nullable',
                'boolean',
            ],
            'balance' => [
                'numeric',
                'min:0'
            ],
            'status' => [
                'required',
                'integer',
                'in:1,2',   
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneHelper::sanitize($this->phone),
        ]);
    }    

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.unique' => 'O nome já está em uso.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',

            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser bank ou cash.',

            'bank_id.exists' => 'O banco selecionado é inválido.',
            'bank_id.required' => 'O banco é obrigatório para contas do tipo bank.',

            'agency.required' => 'A agência é obrigatória para contas bancárias.',
            'number.required' => 'O número da conta é obrigatório para contas bancárias.',

            'balance.numeric' => 'O campo saldo deve ser um número.',
            'balance.min' => 'O saldo não pode ser negativo.',

            'status.required' => 'O campo status é obrigatório.',
            'status.boolean' => 'O campo status deve ser verdadeiro (1) ou falso (0).',

            'is_default.boolean' => 'O campo padrão deve ser verdadeiro (1) ou falso (0).',
        ];
    }
}