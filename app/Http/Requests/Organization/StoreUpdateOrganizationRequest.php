<?php

namespace App\Http\Requests\Organization;

use App\Helpers\CnpjHelper;
use App\Helpers\PhoneHelper;
use App\Rules\CnpjRule;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            // Organizations are the tenants themselves, so the CNPJ is unique
            // globally (not per org like suppliers/adopters).
            'tax_id' => [
                'nullable',
                'max:20',
                new CnpjRule,
                Rule::unique('organizations')->ignore($this->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', new PhoneRule],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_id' => CnpjHelper::sanitize($this->tax_id),
            'phone' => PhoneHelper::sanitize($this->phone),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'legal_name.max' => 'A razão social não pode ter mais de 255 caracteres.',
            'tax_id.max' => 'O CNPJ não pode ter mais de 20 caracteres.',
            'tax_id.unique' => 'O CNPJ já está em uso.',
            'email.email' => 'O email informado é inválido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'website.max' => 'O site não pode ter mais de 255 caracteres.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'city.max' => 'A cidade não pode ter mais de 255 caracteres.',
            'state.size' => 'A UF deve ter exatamente 2 caracteres.',
        ];
    }
}
