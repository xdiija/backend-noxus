<?php

namespace App\Http\Requests\FosterHome;

use App\Helpers\PhoneHelper;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateFosterHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responsible_name' => ['required', 'min:2', 'max:255'],
            'phone' => ['nullable', new PhoneRule],
            'address' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->phone ? PhoneHelper::sanitize($this->phone) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'responsible_name.required' => 'O nome do responsável é obrigatório.',
            'responsible_name.min' => 'O nome do responsável deve ter no mínimo 2 caracteres.',
            'responsible_name.max' => 'O nome do responsável não pode ter mais de 255 caracteres.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1.',
        ];
    }
}
