<?php

namespace App\Http\Requests\AnimalMedication;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateAnimalMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medication_id' => ['required', 'integer', 'exists:medications,id'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            // Null = ongoing course.
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'veterinarian' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'medication_id.required' => 'O medicamento é obrigatório.',
            'medication_id.exists' => 'O medicamento informado é inválido.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início é inválida.',
            'end_date.date' => 'A data de término é inválida.',
            'end_date.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
        ];
    }
}
