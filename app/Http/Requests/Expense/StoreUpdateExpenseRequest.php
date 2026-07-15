<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'min:2', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            // Future dates allowed: an unpaid expense can be due ahead.
            'expense_date' => ['required', 'date'],
            'paid' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'expense_category_id.exists' => 'A categoria informada é inválida.',
            'description.required' => 'A descrição é obrigatória.',
            'description.min' => 'A descrição deve ter no mínimo 2 caracteres.',
            'description.max' => 'A descrição não pode ter mais de 255 caracteres.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor não pode ser negativo.',
            'amount.max' => 'O valor informado é muito alto.',
            'expense_date.required' => 'A data da despesa é obrigatória.',
            'expense_date.date' => 'A data da despesa é inválida.',
            'paid.boolean' => 'O campo pago é inválido.',
        ];
    }
}
