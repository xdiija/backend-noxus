<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Noxus-only "Organizações" menu under "Administrativo".
 *
 * exclusive_noxus = 1 and NO role_menu grants: MenuService hides the entry
 * from every non-Noxus sidebar, User::hasPermission() refuses exclusive menus
 * even if a grant exists, and only Noxus (who bypasses CheckPermissions)
 * reaches the /organizations routes. Idempotent and self-sufficient: upserts
 * the "Administrativo" container itself (same values as MenusTableSeeder), so
 * it never depends on that seeder's hardcoded ids.
 */
class OrganizationMenusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Container menu (no route) — the "Administrativo" group.
        DB::table('menus')->updateOrInsert(
            ['name' => 'Administrativo', 'parent_id' => null],
            [
                'route' => null,
                'icon' => 'admin_panel_settings',
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $parentId = DB::table('menus')
            ->whereNull('parent_id')
            ->where('name', 'Administrativo')
            ->value('id');

        DB::table('menus')->updateOrInsert(
            ['route' => '/organizations'],
            [
                'name' => 'Organizações',
                'icon' => 'corporate_fare',
                'parent_id' => $parentId,
                'order' => 10,
                'status' => 1,
                'exclusive_noxus' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }
}
