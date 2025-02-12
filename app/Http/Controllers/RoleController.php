<?php

namespace App\Http\Controllers;

use App\Helpers\StatusHelper;
use App\Http\Requests\RoleStoreUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Models\Menu;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
    public function __construct(
        protected Menu $menuModel,
        protected Role $roleModel,
        protected PermissionService $permissionService
    ){}

    protected function getRolesBasedOnUser($isActive = false)
    {
        $query = $this->roleModel;
        
        if ($isActive) {
            $query = $query->where('status', StatusHelper::ACTIVE);
        }
    
        if (PermissionService::isNoxusUser()) {
            return $query->get();
        } elseif (PermissionService::isAdminUser()) {
            return $query->where('id', '!=', 1)->get();
        } else {
            return $query->whereNotIn('id', [1, 2])->get();
        }
    }

    public function getActive()
    {   
        return RoleResource::collection(
            $this->getRolesBasedOnUser(true)
        );
    }

    public function index()
    {
        return RoleResource::collection(
            $this->getRolesBasedOnUser()
        );
    }

    public function store(RoleStoreUpdateRequest $request)
    {
        $data = $request->validated();
        $role = $this->roleModel->create([
            'name' => $data["name"],
            'status' => $data['status'] ?? 1
        ]);
        $permissions = $this->permissionService->preparePermissions(
            $data['permissions'], $this->menuModel, 'menu_id'
        );
        $role->menus()->sync($permissions);

        return $role;
    }

    public function show(string $id)
    {
        $query = Role::with(['menus' => function ($query) {
            $query->withPivot(['can_view', 'can_create', 'can_update']);
        }]);
    
        if (!PermissionService::isNoxusUser() && !PermissionService::isAdminUser()) {
            $query->whereNotIn('id', [1, 2]);
        } else if (!PermissionService::isNoxusUser() && PermissionService::isAdminUser()) {
            $query->where('id', '!=', 1);
        }
        
        try {
            $role = $query->findOrFail($id);
            return new RoleResource($role);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Perfil não encontrado ou inacessível.'], 404);
        }
    }

    public function update(RoleStoreUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $role = $this->roleModel->findOrFail($id);
        $role->update([
            'name' => $data["name"],
            'status' => $data['status']
        ]);
        $permissions = $this->permissionService->preparePermissions(
            $data['permissions'], $this->menuModel, 'menu_id'
        );
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

    public function destroy(string $id)
    {
        $role = $this->roleModel->findOrFail($id);
        $role->users()->detach();
        $role->menus()->detach();
        $role->delete();
        return response()->noContent();
    }
}
