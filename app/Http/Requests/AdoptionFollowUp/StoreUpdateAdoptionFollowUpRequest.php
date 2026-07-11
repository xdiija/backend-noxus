<?php

namespace App\Http\Requests\AdoptionFollowUp;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateAdoptionFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'follow_up_date' => ['required', 'date'],
            'contact_method' => ['nullable', 'string', 'max:255'],
            // References adoption_follow_up_statuses (not adoption_statuses).
            'status_id' => ['required', 'integer', 'exists:adoption_follow_up_statuses,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'follow_up_date.required' => 'A data do acompanhamento é obrigatória.',
            'follow_up_date.date' => 'A data do acompanhamento é inválida.',
            'status_id.required' => 'O status é obrigatório.',
            'status_id.exists' => 'O status informado é inválido.',
        ];
    }
}
