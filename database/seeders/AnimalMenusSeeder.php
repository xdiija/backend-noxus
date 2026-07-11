<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the "Abrigo" (shelter operations) sidebar group and its "Animais" child.
 *
 * Unlike the Configurações lookups, animals are organization-scoped operational
 * data, so the Admin role is granted full view/create/update (Noxus bypasses the
 * permission check). Idempotent; depends on RolesTableSeeder.
 */
class AnimalMenusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Container menu (no route) — the "Abrigo" group.
        DB::table('menus')->updateOrInsert(
            ['name' => 'Abrigo', 'parent_id' => null],
            [
                'route' => null,
                'icon' => 'volunteer_activism',
                'order' => 2,
                'status' => 1,
                'exclusive_noxus' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $parentId = DB::table('menus')
            ->whereNull('parent_id')
            ->where('name', 'Abrigo')
            ->value('id');

        DB::table('menus')->updateOrInsert(
            ['route' => '/animals'],
            [
                'name' => 'Animais',
                'icon' => 'pets',
                'parent_id' => $parentId,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $menuId = DB::table('menus')->where('route', '/animals')->value('id');

        // Admin gets full access; every other non-Noxus role starts with none.
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
