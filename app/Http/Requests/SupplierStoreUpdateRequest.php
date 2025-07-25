<?php

namespace App\Http\Requests;

use App\Helpers\PhoneHelper;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierStoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_fantasia' => [
                'required',
                'min:3',
                'max:255'
            ],
            'razao_social' => [
                'required',
                'min:3',
                'max:255'
            ],
            'inscricao_estadual' => [
                'nullable',
                'max:50'
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers')->ignore($this->id)
            ],
            'cnpj' => [
                'required',
                'max:20',
                Rule::unique('suppliers')->ignore($this->id)
            ],
            'phone_1' => [
                'nullable',
                new PhoneRule
            ],
            'phone_2' => [
                'nullable',
                new PhoneRule
            ],
            'status' => [
                'required',
                'integer',
                'in:0,1'
            ]
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone_1' => PhoneHelper::sanitize($this->phone_1),
            'phone_2' => PhoneHelper::sanitize($this->phone_2),
        ]);
    }

    public function messages(): array
    {
        return [
            'nome_fantasia.required' => 'O nome fantasia é obrigatório.',
            'nome_fantasia.min' => 'O nome fantasia deve ter no mínimo 3 caracteres.',
            'nome_fantasia.max' => 'O nome fantasia não pode ter mais de 255 caracteres.',
            'razao_social.required' => 'A razão social é obrigatória.',
            'razao_social.min' => 'A razão social deve ter no mínimo 3 caracteres.',
            'razao_social.max' => 'A razão social não pode ter mais de 255 caracteres.',
            'inscricao_estadual.max' => 'A inscrição estadual não pode ter mais de 50 caracteres.',
            'email.email' => 'O email informado é inválido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'email.unique' => 'O email já está em uso.',
            'cnpj.required' => 'O campo CNPJ é obrigatório.',
            'cnpj.max' => 'O CNPJ não pode ter mais de 20 caracteres.',
            'cnpj.unique' => 'O CNPJ já está em uso.',
            'phone_1.required' => 'O telefone 1 é obrigatório.',
            'phone_2.required' => 'O telefone 2 é obrigatório.',
            'status.required' => 'O status é obrigatório.',
            'status.integer' => 'O status deve ser um número inteiro.',
            'status.in' => 'O status deve ser 0 (inativo) ou 1 (ativo).',
        ];
    }
}
