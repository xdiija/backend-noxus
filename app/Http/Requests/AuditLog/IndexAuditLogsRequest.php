<?php

namespace App\Http\Requests\AuditLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'filter' => ['sometimes', 'string', 'max:255'],
            'entity' => ['sometimes', 'string', 'max:100'],
            'entity_id' => ['sometimes', 'integer'],
            'action' => ['sometimes', Rule::in(['created', 'updated', 'deleted'])],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 10),
            'filter' => $this->input('filter', ''),
        ]);
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'O campo por página deve ser um número inteiro.',
            'per_page.min' => 'O campo por página deve ser no mínimo 1.',
            'per_page.max' => 'O campo por página não pode ser maior que 100.',
            'filter.string' => 'O campo filtro deve ser um texto.',
            'filter.max' => 'O campo filtro não pode ter mais de 255 caracteres.',
            'entity.string' => 'O campo entidade deve ser um texto.',
            'entity.max' => 'O campo entidade não pode ter mais de 100 caracteres.',
            'entity_id.integer' => 'O campo registro deve ser um número inteiro.',
            'action.in' => 'A ação informada é inválida.',
        ];
    }
}
