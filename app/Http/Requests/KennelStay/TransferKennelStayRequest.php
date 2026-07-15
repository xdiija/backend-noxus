<?php

namespace App\Http\Requests\KennelStay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferKennelStayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Destination kennel, org-scoped like the animal FK on store.
            'kennel_id' => [
                'required',
                'integer',
                Rule::exists('kennels', 'id')
                    ->where('organization_id', auth()->user()?->organization_id)
                    ->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'kennel_id.required' => 'O canil de destino é obrigatório.',
            'kennel_id.exists' => 'O canil de destino é inválido.',
            'date.required' => 'A data da transferência é obrigatória.',
            'date.date' => 'A data da transferência é inválida.',
            'date.before_or_equal' => 'A data da transferência não pode ser futura.',
        ];
    }
}
