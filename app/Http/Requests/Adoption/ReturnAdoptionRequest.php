<?php

namespace App\Http\Requests\Adoption;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAdoptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date', 'before_or_equal:today'],
            'return_reason' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_date.required' => 'A data de devolução é obrigatória.',
            'return_date.date' => 'A data de devolução é inválida.',
            'return_date.before_or_equal' => 'A data de devolução não pode ser futura.',
        ];
    }
}
