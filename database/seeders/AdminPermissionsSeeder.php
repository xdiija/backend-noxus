<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Grant the Admin role full (view/create/update) permissions on the
     * users menu. Depends on RolesTableSeeder and MenusTableSeeder having
     * run first. Idempotent per (role, menu).
     *
     * Roles (and menus) are global tables shared by every organization, so
     * managing them is Noxus-only (exclusive_noxus in MenusTableSeeder) —
     * Admin gets no grant there, and any stale grant is removed below.
     */
    public function run(): void
    {
        $now = now();

        // Revoke grants that older seeds may have left on Noxus-only menus.
        $noxusOnlyIds = DB::table('menus')->whereIn('route', ['/roles', '/menus'])->pluck('id');
        DB::table('role_menu')
            ->where('role_id', Role::ADMIN_ROLE)
            ->whereIn('menu_id', $noxusOnlyIds)
            ->delete();

        $menuRoutes = ['/users'];
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
