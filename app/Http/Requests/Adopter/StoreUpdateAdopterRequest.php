<?php

namespace App\Http\Requests\Adopter;

use App\Helpers\CpfHelper;
use App\Helpers\PhoneHelper;
use App\Rules\CpfRule;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateAdopterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255'],
            // CPF is optional and unique within the organization, not globally.
            'cpf' => [
                'nullable',
                'max:50',
                new CpfRule,
                Rule::unique('adopters')
                    ->where('organization_id', auth()->user()?->organization_id)
                    ->ignore($this->id),
            ],
            'phone' => ['nullable', new PhoneRule],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => $this->cpf ? CpfHelper::sanitize($this->cpf) : null,
            'phone' => $this->phone ? PhoneHelper::sanitize($this->phone) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'cpf.unique' => 'Este CPF já está cadastrado para outro adotante.',
            'email.email' => 'O email informado é inválido.',
            'email.max' => 'O email não pode ter mais de 255 caracteres.',
            'birth_date.date' => 'A data de nascimento é inválida.',
            'birth_date.before_or_equal' => 'A data de nascimento não pode ser futura.',
        ];
    }
}
