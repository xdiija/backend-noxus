<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuStoreUpdateRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;

class MenuController extends Controller
{   
    public function __construct(
        protected Menu $menuModel,
        protected Role $roleModel){}

    // Precisa definir a forma que a role será carregada ao efetuar login
    public function getByRoles(int $roleId = 2)
    {
        $menus = $this->menuModel->whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId)
                  ->where('can_view', true)
                  ->where('status', true)
                  ->where('parent_id', null);
        })->with('children')->orderBy('order')->get();

        return MenuResource::collection($menus);
    }
    
    public function index()
    {   
        return  MenuResource::collection($this->menuModel->all());;
    }

    public function store(MenuStoreUpdateRequest $request)
    {
        $data = $request->validated();
        $menu = $this->menuModel->create([
            'name' => $data['name'],
            'route' => $data['route'] ?? null,
            'icon' => $data['icon'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'order' => $data['order'] ?? 0,
            'status' => $data['status'] ?? 0
        ]);

        $permissions = $this->preparePermissions($data['permissions']);
        $menu->roles()->sync($permissions);
        return $menu;
    }

    protected function preparePermissions(array $permissions)
    {
        $permissions = collect($permissions)->mapWithKeys(function ($permission) {
            return [
                $permission['role_id'] => [
                    'can_view' => $permission['can_view'],
                    'can_create' => $permission['can_create'],
                    'can_update' => $permission['can_update'],
                ],
            ];
        })->toArray();

        foreach ($this->roleModel->all() as $role) {
            if (!isset($permissions[$role->id])) {
                $permissions[$role->id] = [
                    'can_view' => 0,
                    'can_create' => 0,
                    'can_update' => 0,
                ];
            }
        }

        return $permissions;
    }

    public function show(string $id)
    {
        return new MenuResource($this->menuModel->with('children')->findOrFail($id));
    }

    public function update(MenuStoreUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $menu = $this->menuModel->findOrFail($id);
        $menu->update([
            'name' => $data['name'],
            'route' => $data['route'] ?? null,
            'icon' => $data['icon'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'order' => $data['order'] ?? 0,
            'status' => $data['status'] ?? 0,
        ]);

        $permissions = $this->preparePermissions($data['permissions']);
        $menu->roles()->sync($permissions);

        return new MenuResource($menu);
    }

}
