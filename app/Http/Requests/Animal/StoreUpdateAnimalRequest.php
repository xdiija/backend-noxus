<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:255'],
            'species_id' => ['required', 'integer', 'exists:species,id'],
            'breed_id' => ['nullable', 'integer', 'exists:breeds,id'],
            'sex' => ['nullable', 'in:M,F'],
            'estimated_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_date_is_estimated' => ['required', 'boolean'],
            'size_id' => ['required', 'integer', 'exists:animal_sizes,id'],
            'color' => ['nullable', 'string', 'max:255'],
            // microchip is globally unique across all animals (data-model/animals.md).
            'microchip' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('animals', 'microchip')->ignore($this->id),
            ],
            'neutered' => ['required', 'boolean'],
            'neuter_date' => ['nullable', 'date', 'before_or_equal:today', 'required_if:neutered,true'],
            'status_id' => ['required', 'integer', 'exists:animal_statuses,id'],
            'notes' => ['nullable', 'string'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],

            // Optional 1:1 intake event (ADR-0003). When present, rescue_date is required.
            'rescue' => ['nullable', 'array'],
            'rescue.rescue_date' => ['required_with:rescue', 'date', 'before_or_equal:now'],
            'rescue.location' => ['nullable', 'string', 'max:255'],
            'rescue.rescued_by' => ['nullable', 'string', 'max:255'],
            'rescue.condition' => ['nullable', 'string', 'max:255'],
            'rescue.description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'species_id.required' => 'A espécie é obrigatória.',
            'species_id.exists' => 'A espécie informada é inválida.',
            'breed_id.exists' => 'A raça informada é inválida.',
            'sex.in' => 'O sexo deve ser M (macho) ou F (fêmea).',
            'estimated_birth_date.date' => 'A data de nascimento é inválida.',
            'estimated_birth_date.before_or_equal' => 'A data de nascimento não pode ser futura.',
            'birth_date_is_estimated.required' => 'Informe se a data de nascimento é estimada.',
            'size_id.required' => 'O porte é obrigatório.',
            'size_id.exists' => 'O porte informado é inválido.',
            'microchip.unique' => 'Este microchip já está cadastrado em outro animal.',
            'neutered.required' => 'Informe se o animal é castrado.',
            'neuter_date.required_if' => 'A data de castração é obrigatória quando o animal é castrado.',
            'neuter_date.before_or_equal' => 'A data de castração não pode ser futura.',
            'status_id.required' => 'O status é obrigatório.',
            'status_id.exists' => 'O status informado é inválido.',
            'tags.*.exists' => 'Uma das etiquetas informadas é inválida.',
            'rescue.rescue_date.required_with' => 'A data de resgate é obrigatória.',
            'rescue.rescue_date.date' => 'A data de resgate é inválida.',
            'rescue.rescue_date.before_or_equal' => 'A data de resgate não pode ser futura.',
        ];
    }
}
