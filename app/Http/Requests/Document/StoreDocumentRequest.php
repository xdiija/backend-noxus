<?php

namespace App\Http\Requests\Document;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // max is in kilobytes → 10 MB.
            'file' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp,doc,docx,odt,xls,xlsx', 'max:10240'],
            'type' => ['nullable', 'string', 'max:100'],
            // Both morph fields together or neither (org-level document).
            'documentable_type' => [
                'nullable',
                'required_with:documentable_id',
                Rule::in(array_keys(Document::OWNER_TYPES)),
            ],
            'documentable_id' => ['nullable', 'required_with:documentable_type', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Selecione um arquivo.',
            'file.file' => 'O envio deve ser um arquivo.',
            'file.mimes' => 'O arquivo deve ser PDF, imagem (JPEG/PNG/WEBP) ou documento (DOC/DOCX/ODT/XLS/XLSX).',
            'file.max' => 'O arquivo não pode ter mais de 10 MB.',
            'type.string' => 'O campo tipo deve ser um texto.',
            'type.max' => 'O campo tipo não pode ter mais de 100 caracteres.',
            'documentable_type.in' => 'O tipo de vínculo informado é inválido.',
            'documentable_type.required_with' => 'Informe o tipo de vínculo do documento.',
            'documentable_id.required_with' => 'Informe o registro vinculado ao documento.',
            'documentable_id.integer' => 'O registro vinculado deve ser um número inteiro.',
        ];
    }
}
