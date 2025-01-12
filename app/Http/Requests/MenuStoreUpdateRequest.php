<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuStoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'min:3',
                'max:255',
                Rule::unique('menus')->whereNull('deleted_at')->ignore($this->id)
            ],
            'route' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('menus')->whereNull('deleted_at')->ignore($this->id)
            ],
            'icon' => [
                'required',
                'string',
                'max:255'
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:menus,id'
            ],
            'order' => [
                'required',
                'integer'
            ],
            'status' => [
                'required',
                'integer',
                'in:1,2'
            ],
            'exclusive_noxus' => [
                'required',
                'boolean'
            ],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.unique' => 'O campo nome já está em uso.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'route.unique' => 'A rota já está em uso.',
            'route.max' => 'A rota não pode ter mais de 255 caracteres.',
            'icon.required' => 'O campo ícone é obrigatório.',
            'icon.max' => 'O ícone não pode ter mais de 255 caracteres.',
            'parent_id.integer' => 'O campo ID do pai deve ser um número inteiro.',
            'parent_id.exists' => 'O menu pai especificado não existe.',
            'order.required' => 'O campo ordem é obrigatório.',
            'order.integer' => 'O campo ordem deve ser um número inteiro.',
            'status.required' => 'O campo status é obrigatório.',
            'status.integer' => 'O campo status deve ser um número inteiro.',
            'status.in' => 'O campo status deve ser 1 ou 2.',
            'exclusive_noxus.required' => 'O campo exclusive_noxus é obrigatório.',
            'exclusive_noxus.boolean' => 'O campo exclusive_noxus deve ser um valor booleano.'
        ];
    }
}