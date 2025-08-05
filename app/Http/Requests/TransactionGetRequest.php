<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class TransactionGetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'numeric','max:100'],
            'type' => ['nullable', Rule::in(['expense', 'income', 'transfer'])],
            'payment_type' => ['nullable', 'array'], 
            'payment_type.*' => ['required', 'string', Rule::in(['single', 'installment', 'recurrent'])],
            'category' => ['nullable', 'exists:transaction_categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date',
                function ($attribute, $value, $fail) {
                    $from = $this->input('date_from');
                    $to = $value;

                    if ($from && $to) {
                        try {
                            $fromDate = Carbon::createFromFormat('Y-m-d', $from);
                            $toDate = Carbon::createFromFormat('Y-m-d', $to);
                        } catch (\Exception $e) {
                            $fail('As datas devem estar no formato yyyy-mm-dd.');
                            return;
                        }

                        if ($fromDate->gt($toDate)) {
                            $fail('A data inicial não pode ser maior que a data final.');
                        }

                        if ($fromDate->diffInDays($toDate) > 366) {
                            $fail('O intervalo de datas não pode ser maior que 1 ano.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.numeric' => 'O valor de "por página" deve ser um número.',
            'per_page.max' => 'O valor de "por página" não pode ser maior que 100.',
            'type.in' => 'O tipo deve ser "expense" ou "income".',
            'payment_type.in' => 'O tipo de pagamento deve ser "single", "installment" ou "recurrent".',
            'category.exists' => 'A categoria selecionada é inválida.',
            'date_from.date' => 'A data inicial deve ser uma data válida.',
            'date_to.date' => 'A data final deve ser uma data válida.',
        ];
    }
}
