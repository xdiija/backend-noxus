<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpcomingAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'days' => (int) $this->input('days', 30),
        ]);
    }

    public function messages(): array
    {
        return [
            'days.integer' => 'O campo dias deve ser um número inteiro.',
            'days.min' => 'O campo dias deve ser no mínimo 1.',
            'days.max' => 'O campo dias não pode ser maior que 365.',
        ];
    }
}
