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
                Rule::unique('menus')->ignore($this->menu)
            ],
            'route' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('menus')->ignore($this->menu)
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
                'in:0,1'
            ],
            'permissions' => [
                'required',
                'array'
            ],
            'permissions.*.role_id' => [
                'required',
                'integer',
                'exists:roles,id'
            ],
            'permissions.*.can_view' => [
                'required',
                'boolean'
            ],
            'permissions.*.can_create' => [
                'required',
                'boolean'
            ],
            'permissions.*.can_update' => [
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
            'status.in' => 'O campo status deve ser 0 ou 1.',
            'permissions.required' => 'O campo permissões é obrigatório.',
            'permissions.array' => 'As permissões devem ser um array.',
            'permissions.*.role_id.required' => 'O campo role_id é obrigatório nas permissões.',
            'permissions.*.role_id.integer' => 'O campo role_id deve ser um número inteiro.',
            'permissions.*.role_id.exists' => 'O role_id especificado não existe.',
            'permissions.*.can_view.required' => 'O campo can_view é obrigatório nas permissões.',
            'permissions.*.can_create.required' => 'O campo can_create é obrigatório nas permissões.',
            'permissions.*.can_update.required' => 'O campo can_update é obrigatório nas permissões.',
            'permissions.*.can_view.boolean' => 'O campo can_view deve ser um valor booleano.',
            'permissions.*.can_create.boolean' => 'O campo can_create deve ser um valor booleano.',
            'permissions.*.can_update.boolean' => 'O campo can_update deve ser um valor booleano.',
        ];
    }
}