<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the `type` metadata is editable; replacing the file itself is a
 * delete + new upload (mirrors the animal-images flow).
 */
class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.string' => 'O campo tipo deve ser um texto.',
            'type.max' => 'O campo tipo não pode ter mais de 100 caracteres.',
        ];
    }
}
