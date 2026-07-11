<?php

namespace App\Http\Requests\Adoption;

use Illuminate\Foundation\Http\FormRequest;

class CancelAdoptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
