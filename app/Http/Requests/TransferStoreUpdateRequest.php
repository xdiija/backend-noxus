<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'exists:accounts,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => [ 'required', 'date', 'date_format:Y-m-d', 'before_or_equal:today',],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_account_id.required' => 'A conta de origem é obrigatória.',
            'from_account_id.exists' => 'A conta de origem selecionada é inválida.',
            'payment_method_id.required' => 'O método de pagamento é obrigatório.',
            'payment_method_id.exists' => 'O método de pagamento selecionado é inválido.',
            'to_account_id.required' => 'A conta de destino é obrigatória.',
            'to_account_id.exists' => 'A conta de destino selecionada é inválida.',
            'to_account_id.different' => 'A conta de destino deve ser diferente da conta de origem.',
            'amount.required' => 'O valor da transferência é obrigatório.',
            'amount.numeric' => 'O valor da transferência deve ser numérico.',
            'amount.min' => 'O valor da transferência deve ser maior que zero.',
            'transfer_date.required' => 'A data da transferência é obrigatória.',
            'transfer_date.date' => 'A data da transferência deve ser válida.',
            'transfer_date.before_or_equal' => 'A data da transferência não pode ser no futuro.',
            'transfer_date.date_format' => 'A data da transferência deve estar no formato YYYY-MM-DD (ex: 2025-07-06).',
            'description.max' => 'A descrição não pode ter mais que 255 caracteres.',
        ];
    }
}
