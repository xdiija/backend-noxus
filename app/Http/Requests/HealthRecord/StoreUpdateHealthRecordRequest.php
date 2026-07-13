<?php

namespace App\Http\Requests\HealthRecord;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // e.g. exame, vermifugação, observação — free text, no fixed list.
            'type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'veterinarian' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo do registro é obrigatório.',
            'type.max' => 'O tipo do registro não pode exceder 255 caracteres.',
            'event_date.required' => 'A data do evento é obrigatória.',
            'event_date.date' => 'A data do evento é inválida.',
        ];
    }
}
