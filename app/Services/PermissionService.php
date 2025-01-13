<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

class PermissionService
{
    public function preparePermissions(array $permissions, Model $model, string $relationId): array
    {
        User::forgetUserPermissionsCache();
        
        $preparedPermissions = collect($permissions)->mapWithKeys(function ($permission) use ($relationId) {
            return [
                $permission[$relationId] => [
                    'can_view' => $permission['can_view'],
                    'can_create' => $permission['can_create'],
                    'can_update' => $permission['can_update'],
                ],
            ];
        })->toArray();

        foreach ($model->all() as $entity) {
            if (!isset($preparedPermissions[$entity->id])) {
                $preparedPermissions[$entity->id] = [
                    'can_view' => 0,
                    'can_create' => 0,
                    'can_update' => 0,
                ];
            }
        }

        return $preparedPermissions;
    }

    public static function isNoxusUser(): bool
    {
        $userRoles = auth()->user()->roles->pluck('id');
        return $userRoles->contains(Role::NOXUS_ROLE);
    }

    public static function isAdminUser(): bool
    {
        $userRoles = auth()->user()->roles->pluck('id');
        return $userRoles->contains(Role::ADMIN_ROLE);
    }
}