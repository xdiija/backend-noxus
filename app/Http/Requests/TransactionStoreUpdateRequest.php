<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'customer_id' => [ 'required_if:type,income', 'exists:customers,id'],
            'supplier_id' => [ 'required_if:type,expense', 'exists:suppliers,id'],
            'cost_center_id' => [ 'required_if:type,expense', 'exists:cost_centers,id'],
            'payment_type' => ['required', Rule::in(['single', 'installment'])],
            'payments' => ['required', 'array'],
            'payments.*.id' => ['nullable', 'exists:payments,id'],
            'payments.*.payment_number' => ['nullable', 'integer'],
            'payments.*.payment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'payments.*.account_id' => [
                'required_with:payments.*.payment_date',
                'exists:accounts,id',
            ],
            'payments.*.payment_method_id' => [
                'required_with:payments.*.payment_date',
                'exists:payment_methods,id',
            ],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.discount' => ['required', 'numeric', 'min:0'],
            'payments.*.increase' => ['required', 'numeric', 'min:0'],
            'payments.*.due_date' => ['required', 'date'],
            'payments.*.status' => ['nullable', 'integer'],
        ];
    }

   public function messages(): array
    {
        return [
            'description.max' => 'A descrição não pode exceder 1000 caracteres.',

            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.exists' => 'A categoria selecionada é inválida.',

            'payment_type.required' => 'O tipo de pagamento é obrigatório.',
            'payment_type.in' => 'O tipo de pagamento deve ser um dos seguintes valores: single ou installment.',

            'payments.required' => 'É necessário informar pelo menos um pagamento.',
            'payments.array' => 'O formato dos pagamentos é inválido.',

            'payments.*.id.exists' => 'O pagamento informado é inválido.',

            'payments.*.payment_number.integer' => 'O número do pagamento deve ser um número inteiro.',

            'payments.*.payment_date.date' => 'A data de pagamento deve ser válida.',
            'payments.*.payment_date.before_or_equal' => 'A data de pagamento não pode ser futura.',

            'payments.*.account_id.required_with' => 'A conta é obrigatória quando a data de pagamento é informada.',
            'payments.*.account_id.exists' => 'A conta selecionada é inválida.',

            'payments.*.payment_method_id.required_with' => 'O método de pagamento é obrigatório quando a data de pagamento é informada.',
            'payments.*.payment_method_id.exists' => 'O método de pagamento selecionado é inválido.',

            'payments.*.amount.required' => 'O valor do pagamento é obrigatório.',
            'payments.*.amount.numeric' => 'O valor do pagamento deve ser numérico.',
            'payments.*.amount.min' => 'O valor do pagamento deve ser de pelo menos 0,01.',

            'payments.*.discount.required' => 'O campo de desconto é obrigatório.',
            'payments.*.discount.numeric' => 'O desconto deve ser numérico.',
            'payments.*.discount.min' => 'O desconto não pode ser negativo.',

            'payments.*.increase.required' => 'O campo de acréscimo é obrigatório.',
            'payments.*.increase.numeric' => 'O acréscimo deve ser numérico.',
            'payments.*.increase.min' => 'O acréscimo não pode ser negativo.',

            'payments.*.due_date.required' => 'A data de vencimento é obrigatória para cada pagamento.',
            'payments.*.due_date.date' => 'A data de vencimento deve ser válida.',

            'payments.*.status.integer' => 'O status deve ser um número inteiro.',
        ];
    }
}

