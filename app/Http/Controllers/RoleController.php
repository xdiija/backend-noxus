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
            'status' => $data['status'] ?? 0
        ]);
        $permissions = $this->permissionService
            ->preparePermissions( $data['permissions'], $this->menuModel, 'menu_id' );
        $role->menus()->sync($permissions);

        return $role;
    }

    public function show(string $id)
    {
        return new RoleResource($this->roleModel->findOrFail($id));
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
}
