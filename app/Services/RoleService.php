<?php

namespace App\Services;

use App\DTOs\Role\RoleDTO;
use App\Enums\Status;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Roles the authenticated user is allowed to manage. Noxus sees all, Admin sees
     * everything but the Noxus role, regular users see neither Noxus nor Admin roles.
     */
    public function list(bool $onlyActive = false): Collection
    {
        $query = Role::query();

        if ($onlyActive) {
            $query->where('status', Status::ACTIVE->value);
        }

        if (PermissionService::isNoxusUser()) {
            return $query->get();
        }

        if (PermissionService::isAdminUser()) {
            return $query->where('id', '!=', Role::NOXUS_ROLE)->get();
        }

        return $query->whereNotIn('id', [Role::NOXUS_ROLE, Role::ADMIN_ROLE])->get();
    }

    public function find(string $id): Role
    {
        $query = Role::with(['menus' => function ($query) {
            $query->withPivot(['can_view', 'can_create', 'can_update']);
        }]);

        if (!PermissionService::isNoxusUser()) {
            if (PermissionService::isAdminUser()) {
                $query->where('id', '!=', Role::NOXUS_ROLE);
            } else {
                $query->whereNotIn('id', [Role::NOXUS_ROLE, Role::ADMIN_ROLE]);
            }
        }

        return $query->findOrFail($id);
    }

    public function create(RoleDTO $dto): Role
    {
        $role = Role::create($dto->toArray());
        $this->syncPermissions($role, $dto);

        return $role;
    }

    public function update(string $id, RoleDTO $dto): Role
    {
        $role = Role::findOrFail($id);
        $role->update($dto->toArray());
        $this->syncPermissions($role, $dto);

        return $role;
    }

    public function changeStatus(string $id): Role
    {
        $role = Role::findOrFail($id);
        $role->status = $role->status === Status::ACTIVE->value
            ? Status::INACTIVE->value
            : Status::ACTIVE->value;
        $role->save();

        return $role;
    }

    public function delete(string $id): void
    {
        $role = Role::findOrFail($id);
        $role->users()->update(['role_id' => null]);
        $role->menus()->detach();
        $role->delete();
    }

    private function syncPermissions(Role $role, RoleDTO $dto): void
    {
        $permissions = $this->permissionService->preparePermissions(
            $dto->permissions, new Menu(), 'menu_id'
        );
        $role->menus()->sync($permissions);
    }
}
