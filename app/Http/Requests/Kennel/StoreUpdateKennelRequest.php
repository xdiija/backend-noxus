<?php

namespace App\Http\Requests\Kennel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateKennelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = auth()->user()?->organization_id;

        return [
            // Unique per organization (soft-deleted kennels don't block reuse).
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('kennels')
                    ->where('organization_id', $orgId)
                    ->withoutTrashed()
                    ->ignore($this->id),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'size_id' => ['nullable', 'integer', 'exists:animal_sizes,id'],
            // Readable statuses only: system defaults or the org's own rows.
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('kennel_statuses', 'id')->where(
                    fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $orgId)
                ),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do canil é obrigatório.',
            'name.max' => 'O nome do canil não pode ter mais de 255 caracteres.',
            'name.unique' => 'Já existe um canil com este nome.',
            'capacity.required' => 'A capacidade é obrigatória.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1.',
            'size_id.exists' => 'O porte informado é inválido.',
            'status_id.exists' => 'O status informado é inválido.',
        ];
    }
}
