<?php

namespace App\Http\Requests\Donor;

use App\Helpers\PhoneHelper;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', new PhoneRule],
            'notes' => ['nullable', 'string'],
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
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'O email informado é inválido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'notes.string' => 'As observações devem ser um texto.',
        ];
    }
}
