<?php

namespace App\Http\Controllers;

use App\Helpers\StatusHelper;
use App\Http\Requests\MenuStoreUpdateRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\Role;
use App\Services\PermissionService;

class MenuController extends Controller
{   
    public function __construct(
        protected Menu $menuModel,
        protected Role $roleModel,
        protected PermissionService $permissionService){}

    public function getByRoles()
    {   
        $userRoles = auth()->user()->roles->pluck('id');

        $menus = $this->menuModel
            ->whereHas('roles', function ($query) use ($userRoles) {
                $query
                    ->whereIn('role_id', $userRoles)
                    ->where('can_view', true)
                    ->where('status', StatusHelper::ACTIVE);
            })
            ->where('parent_id', null)
            ->where('status', StatusHelper::ACTIVE) 
            ->with(['children' => function ($query) use ($userRoles) {
                $query
                    ->where('status', StatusHelper::ACTIVE)
                    ->whereHas('roles', function ($query) use ($userRoles) {
                        $query->whereIn('role_id', $userRoles)
                        ->where('can_view', true)
                        ->where('status', StatusHelper::ACTIVE);
                    });
            }])
            ->orderBy('order')
            ->get();

        return MenuResource::collection($menus);
    }

    public function getActive()
    {   
        $menus = $this->menuModel
            ->where('parent_id', null)
            ->where('status', StatusHelper::ACTIVE) 
            ->with(['children' => function ($query) {
                $query->where('status', StatusHelper::ACTIVE);
            }])
            ->orderBy('order')
            ->get();

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
            'status' => $data['status'] ?? StatusHelper::ACTIVE
        ]);

        $permissions = $this->permissionService
            ->preparePermissions( $data['permissions'], $this->roleModel, 'role_id' );
        $menu->roles()->sync($permissions);
        return $menu;
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
            'status' => $data[''],
        ]);

        $permissions = $this->permissionService
            ->preparePermissions( $data['permissions'], $this->roleModel, 'role_id' );
        $menu->roles()->sync($permissions);

        return new MenuResource($menu);
    }

}
