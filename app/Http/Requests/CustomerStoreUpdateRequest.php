<?php

namespace App\Http\Requests;

use App\Helpers\PhoneHelper;
use App\Helpers\CpfHelper;
use App\Rules\PhoneRule;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerStoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'min:3',
                'max:255'
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($this->id)
            ],
            'cpf' => [
                'required',
                'max:50',
                new CpfRule,
                Rule::unique('customers')->ignore($this->id)
            ],
            'phone_1' => [
                'required',
                new PhoneRule
            ],
            'phone_2' => [
                'required',
                new PhoneRule
            ],
            'status' => [
                'required',
                'integer',
                'in:1,2'
            ]
        ];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone_1' => PhoneHelper::sanitize($this->phone_1),
            'phone_2' => PhoneHelper::sanitize($this->phone_2),
            'cpf' => CpfHelper::sanitize($this->cpf),
        ]);
    }    

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'O email informado é inválido!',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'email.unique' => 'O email já está em uso.',
            'cpf.required' => 'O campo cpf é obrigatório.',
            'phone_1.required' => 'O campo telefone 1 é obrigatório.',
            'phone_2.required' => 'O campo telefone 2 é obrigatório.',
            'status.required' => 'O campo status é obrigatório.',
            'status.integer' => 'O campo status deve ser um número inteiro.',
            'status.in' => 'O campo status deve ser 1 ou 2.',
        ];
    }
}