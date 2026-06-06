<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class IndexSuppliersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'filter'   => ['sometimes', 'string', 'max:255'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 10),
            'filter'   => $this->input('filter', ''),
        ]);
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'O campo por página deve ser um número inteiro.',
            'per_page.min'     => 'O campo por página deve ser no mínimo 1.',
            'per_page.max'     => 'O campo por página não pode ser maior que 100.',
            'filter.string'    => 'O campo filtro deve ser um texto.',
            'filter.max'       => 'O campo filtro não pode ter mais de 255 caracteres.',
        ];
    }
}
