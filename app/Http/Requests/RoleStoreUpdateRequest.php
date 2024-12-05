<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreUpdateRequest extends FormRequest
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
                Rule::unique('roles')->ignore($this->role)
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
            'permissions.*.menu_id' => [
                'required',
                'integer',
                'exists:menus,id'
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
            'status.required' => 'O campo status é obrigatório.',
            'status.integer' => 'O campo status deve ser um número inteiro.',
            'status.in' => 'O campo status deve ser 0 ou 1.',
            'permissions.required' => 'O campo permissões é obrigatório.',
            'permissions.array' => 'As permissões devem ser um array.',
            'permissions.*.menu_id.required' => 'O campo menu_id é obrigatório nas permissões.',
            'permissions.*.menu_id.integer' => 'O campo menu_id deve ser um número inteiro.',
            'permissions.*.menu_id.exists' => 'O menu_id especificado não existe.',
            'permissions.*.can_view.required' => 'O campo can_view é obrigatório nas permissões.',
            'permissions.*.can_create.required' => 'O campo can_create é obrigatório nas permissões.',
            'permissions.*.can_update.required' => 'O campo can_update é obrigatório nas permissões.',
            'permissions.*.can_view.boolean' => 'O campo can_view deve ser um valor booleano.',
            'permissions.*.can_create.boolean' => 'O campo can_create deve ser um valor booleano.',
            'permissions.*.can_update.boolean' => 'O campo can_update deve ser um valor booleano.',
        ];
    }
}