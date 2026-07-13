<?php

namespace App\Http\Requests\Volunteer;

use App\Helpers\PhoneHelper;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateVolunteerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255'],
            'phone' => ['nullable', new PhoneRule],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
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
            'role.max' => 'A função não pode ter mais de 255 caracteres.',
            'status.required' => 'O status é obrigatório.',
            'status.boolean' => 'O status é inválido.',
        ];
    }
}
