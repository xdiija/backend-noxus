<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Menu;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Per-resource permissions for the authenticated user, keyed by the menu
     * route without its leading slash (e.g. "users"). Consumed by the frontend
     * to gate edit/create/delete controls. Noxus gets everything.
     *
     * @return array<string, array{view: bool, create: bool, update: bool}>
     */
    public static function permissionsMap(): array
    {
        if (self::isNoxusUser()) {
            return Menu::query()
                ->whereNotNull('route')
                ->pluck('route')
                ->mapWithKeys(fn ($route) => [
                    ltrim($route, '/') => ['view' => true, 'create' => true, 'update' => true],
                ])
                ->all();
        }

        return DB::table('menus')
            ->join('role_menu', 'menus.id', '=', 'role_menu.menu_id')
            ->join('roles', 'roles.id', '=', 'role_menu.role_id')
            ->where('role_menu.role_id', auth()->user()->role_id)
            ->where('roles.status', Status::ACTIVE->value)
            ->where('menus.exclusive_noxus', false)
            ->whereNotNull('menus.route')
            ->get(['menus.route', 'role_menu.can_view', 'role_menu.can_create', 'role_menu.can_update'])
            ->mapWithKeys(fn ($r) => [
                ltrim($r->route, '/') => [
                    'view' => (bool) $r->can_view,
                    'create' => (bool) $r->can_create,
                    'update' => (bool) $r->can_update,
                ],
            ])
            ->all();
    }

    public function preparePermissions(array $permissions, Model $model, string $relationId): array
    {
        User::forgetUserPermissionsCache();

        $submitted = collect($permissions)->mapWithKeys(function ($permission) use ($relationId) {
            return [
                $permission[$relationId] => [
                    'can_view' => $permission['can_view'],
                    'can_create' => $permission['can_create'],
                    'can_update' => $permission['can_update'],
                ],
            ];
        });

        // Only leaf menus (those with a route) are permission units; container menus
        // (route null) derive their visibility from their children and never carry permissions.
        $preparedPermissions = [];

        foreach ($model->whereNotNull('route')->pluck('id') as $menuId) {
            $preparedPermissions[$menuId] = $submitted->get($menuId, [
                'can_view' => 0,
                'can_create' => 0,
                'can_update' => 0,
            ]);
        }

        return $preparedPermissions;
    }

    public static function isNoxusUser(): bool
    {
        return (int) auth()->user()->role_id === Role::NOXUS_ROLE;
    }

    public static function isAdminUser(): bool
    {
        return (int) auth()->user()->role_id === Role::ADMIN_ROLE;
    }

    public static function isRegularUser(): bool
    {
        return !self::isNoxusUser() && !self::isAdminUser();
    }
}