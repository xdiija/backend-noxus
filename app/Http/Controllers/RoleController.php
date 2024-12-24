<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Models\Menu;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class RoleController extends Controller
{

    public function __construct(
        protected Menu $menuModel,
        protected Role $roleModel,
        protected PermissionService $permissionService){}

    public function index()
    {
        return  RoleResource::collection($this->roleModel->all());
    }
    public function store(RoleStoreUpdateRequest $request)
    {
        $data = $request->validated();
        $role = $this->roleModel->create([
            'name' => $data["name"],
            'status' => $data['status'] ?? 1
        ]);
        $permissions = $this->permissionService
            ->preparePermissions( $data['permissions'], $this->menuModel, 'menu_id' );
        $role->menus()->sync($permissions);

        return $role;
    }

    public function show(string $id)
    {
        $role = Role::with(['menus' => function ($query) {
            $query->withPivot(['can_view', 'can_create', 'can_update']);
        }])->findOrFail($id);

        return new RoleResource($role);
    }

    public function update(RoleStoreUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $role = $this->roleModel->findOrFail($id);
        $role->update([
            'name' => $data["name"],
            'status' => $data['status']
        ]);
        $permissions = $this->permissionService
            ->preparePermissions( $data['permissions'], $this->menuModel, 'menu_id' );
        $role->menus()->sync($permissions);

        return new RoleResource($role);
    }

    public function changeStatus(string $id)
    {   
        $role = $this->roleModel->findOrFail($id);
        $role->status = $role->status === 1 ? 2 : 1;
        $role->save();
        return new RoleResource($role);
    }
}
