<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyTrendsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'months' => (int) $this->input('months', 6),
        ]);
    }

    public function messages(): array
    {
        return [
            'months.integer' => 'O campo meses deve ser um número inteiro.',
            'months.min' => 'O campo meses deve ser no mínimo 1.',
            'months.max' => 'O campo meses não pode ser maior que 24.',
        ];
    }
}
