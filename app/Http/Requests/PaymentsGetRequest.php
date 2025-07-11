<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class PaymentsGetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'numeric','max:100'],
            'date_filter_option' => ['nullable', Rule::in(['due_date', 'created_at'])],
            'status' => ['nullable', 'array'], 
            'status.*' => ['required', 'string', Rule::in(['overdue', 'pending', 'paid'])],
            'type' => ['nullable', Rule::in(['expense', 'income'])],
            'account' => ['nullable', 'exists:accounts,id'],
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
            'date_filter_option.in' => 'A opção de filtro de data deve ser "due_date" ou "payment_date".',
            'status.in' => 'O status deve ser um dos seguintes: overdue, pending ou paid.',
            'type.in' => 'O tipo deve ser um dos seguintes: all, expense ou income.',
            'date_from.date' => 'A data inicial deve ser uma data válida.',
            'date_to.date' => 'A data final deve ser uma data válida.',
        ];
    }
}
