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
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'exists:transaction_categories,id'],
            'payments' => ['required', 'array'],
            'payments.*.id' => ['nullable', 'exists:payments,id'],
            'payments.*.account_id' => ['required', 'exists:accounts,id'],
            'payments.*.payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.discount' => ['required', 'numeric', 'min:0'],
            'payments.*.increase' => ['required', 'numeric', 'min:0'],
            'payments.*.due_date' => ['required', 'date'],
            'payments.*.payment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'payments.*.status' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
{
    return [
        'description.max' => 'A descrição não pode exceder 1000 caracteres.',
        'category_id.required' => 'A categoria é obrigatória.',
        'category_id.exists' => 'A categoria selecionada é inválida.',

        'payments.required' => 'Os pagamentos são obrigatórios.',
        'payments.array' => 'O formato dos pagamentos é inválido.',

        'payments.*.account_id.required' => 'A conta é obrigatória para cada pagamento.',
        'payments.*.account_id.exists' => 'A conta selecionada é inválida.',

        'payments.*.payment_method_id.required' => 'O método de pagamento é obrigatório para cada pagamento.',
        'payments.*.payment_method_id.exists' => 'O método de pagamento selecionado é inválido.',

        'payments.*.amount.required' => 'O valor do pagamento é obrigatório.',
        'payments.*.amount.integer' => 'O valor do pagamento deve ser um número inteiro em centavos.',
        'payments.*.amount.min' => 'O valor do pagamento deve ser de pelo menos 1 centavo.',

        'payments.*.discount.integer' => 'O desconto deve ser um número inteiro em centavos.',
        'payments.*.discount.min' => 'O desconto não pode ser negativo.',

        'payments.*.increase.integer' => 'O acréscimo deve ser um número inteiro em centavos.',
        'payments.*.increase.min' => 'O acréscimo não pode ser negativo.',

        'payments.*.due_date.required' => 'A data de vencimento é obrigatória para cada pagamento.',
        'payments.*.due_date.date' => 'A data de vencimento deve ser válida.',

        'payments.*.payment_date.date' => 'A data de pagamento deve ser válida.',

        'payments.*.status.integer' => 'O status deve ser um número inteiro.',
    ];
}
}
