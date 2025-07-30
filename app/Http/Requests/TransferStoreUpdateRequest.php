<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_account_id.required' => 'A conta de origem é obrigatória.',
            'from_account_id.exists' => 'A conta de origem selecionada é inválida.',

            'to_account_id.required' => 'A conta de destino é obrigatória.',
            'to_account_id.exists' => 'A conta de destino selecionada é inválida.',
            'to_account_id.different' => 'A conta de destino deve ser diferente da conta de origem.',

            'amount.required' => 'O valor da transferência é obrigatório.',
            'amount.numeric' => 'O valor da transferência deve ser numérico.',
            'amount.min' => 'O valor da transferência deve ser maior que zero.',

            'transfer_date.required' => 'A data da transferência é obrigatória.',
            'transfer_date.date' => 'A data da transferência deve ser válida.',
            'transfer_date.before_or_equal' => 'A data da transferência não pode ser no futuro.',

            'description.max' => 'A descrição não pode ter mais que 255 caracteres.',
        ];
    }
}
