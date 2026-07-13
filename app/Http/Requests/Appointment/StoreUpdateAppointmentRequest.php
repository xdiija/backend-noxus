<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date'],
            'veterinarian' => ['nullable', 'string', 'max:255'],
            'clinic' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            // Outcome after the visit.
            'result' => ['nullable', 'string'],
            // Suggested follow-up date.
            'next_visit' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.required' => 'A data da consulta é obrigatória.',
            'appointment_date.date' => 'A data da consulta é inválida.',
            'next_visit.date' => 'A data da próxima visita é inválida.',
        ];
    }
}
