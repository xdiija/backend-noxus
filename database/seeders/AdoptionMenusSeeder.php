<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the "Adotantes" and "Adoções" children of the "Abrigo" (shelter
 * operations) sidebar group (Module 3).
 *
 * Like the "Animais" menu, adopters/adoptions are organization-scoped
 * operational data, so the Admin role is granted full view/create/update (Noxus
 * bypasses the permission check). The nested follow-up and read-only adoption
 * status-lookup routes reuse the `adoptions` grant, so no extra menus are needed.
 * Idempotent; depends on RolesTableSeeder and the "Abrigo" group.
 */
class AdoptionMenusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Container menu (no route) — the "Abrigo" group. Created here too so the
        // seeder is order-independent from AnimalMenusSeeder (both are idempotent).
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

        $children = [
            ['name' => 'Adotantes', 'route' => '/adopters', 'icon' => 'people', 'order' => 2],
            ['name' => 'Adoções', 'route' => '/adoptions', 'icon' => 'favorite', 'order' => 3],
        ];

        foreach ($children as $child) {
            DB::table('menus')->updateOrInsert(
                ['route' => $child['route']],
                [
                    'name' => $child['name'],
                    'icon' => $child['icon'],
                    'parent_id' => $parentId,
                    'order' => $child['order'],
                    'status' => 1,
                    'exclusive_noxus' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $menuId = DB::table('menus')->where('route', $child['route'])->value('id');

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
}
