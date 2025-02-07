<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionStoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'due_date' => [
                'required',
                'date',
            ],
            'payment_date' => [
                'nullable',
                'date'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'category_id' => [
                'required',
                'exists:transaction_categories,id',
            ],
            'account_id' => [
                'required',
                'exists:accounts,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser maior que 0.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.date' => 'A data de vencimento deve ser válida.',
            'payment_date.date' => 'A data de pagamento deve ser válida.',
            'description.max' => 'A descrição não pode exceder 1000 caracteres.',
            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'account_id.required' => 'A conta é obrigatória.',
            'account_id.exists' => 'A conta selecionada é inválida.',
        ];
    }
}
