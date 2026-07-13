<?php

namespace App\Http\Requests\FosterPlacement;

use Illuminate\Foundation\Http\FormRequest;

class CloseFosterPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exit_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'exit_date.required' => 'A data de saída é obrigatória.',
            'exit_date.date' => 'A data de saída é inválida.',
            'exit_date.before_or_equal' => 'A data de saída não pode ser futura.',
        ];
    }
}
