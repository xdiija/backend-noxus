<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Grant the Admin role full (view/create/update) permissions on the
     * users and roles menus. Depends on RolesTableSeeder and
     * MenusTableSeeder having run first. Idempotent per (role, menu).
     */
    public function run(): void
    {
        $now = now();

        $menuRoutes = ['/users', '/roles'];
        $menuIds = DB::table('menus')->whereIn('route', $menuRoutes)->pluck('id', 'route');

        foreach ($menuRoutes as $route) {
            $menuId = $menuIds[$route] ?? null;

            if (! $menuId) {
                continue;
            }

            DB::table('role_menu')->updateOrInsert(
                ['role_id' => Role::ADMIN_ROLE, 'menu_id' => $menuId],
                [
                    'can_view' => 1,
                    'can_create' => 1,
                    'can_update' => 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
