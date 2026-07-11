<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the "Configurações" (system settings) sidebar menu and its children —
 * the reference-data screens (breeds, sizes, statuses, tags).
 *
 * The lookups are readable by everyone (the data is consumed elsewhere, e.g.
 * the Animal form), so the menus are NOT exclusive_noxus and every role is
 * granted `can_view`. Create/update are left at `0`, so only Noxus manages them
 * (Noxus bypasses permission checks). Idempotent; depends on RolesTableSeeder.
 */
class ShelterMenusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Container menu (no route) — the "Configurações" group.
        DB::table('menus')->updateOrInsert(
            ['name' => 'Configurações', 'parent_id' => null],
            [
                'route' => null,
                'icon' => 'settings',
                'order' => 10,
                'status' => 1,
                'exclusive_noxus' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $parentId = DB::table('menus')
            ->whereNull('parent_id')
            ->where('name', 'Configurações')
            ->value('id');

        $children = [
            ['name' => 'Raças', 'route' => '/breeds', 'icon' => 'pets', 'order' => 1],
            ['name' => 'Portes', 'route' => '/animal-sizes', 'icon' => 'straighten', 'order' => 2],
            ['name' => 'Status dos Animais', 'route' => '/animal-statuses', 'icon' => 'flag', 'order' => 3],
            ['name' => 'Etiquetas', 'route' => '/tags', 'icon' => 'sell', 'order' => 4],
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
        }

        // Grant read-only visibility to every role: can_view=1, but
        // can_create/can_update=0 so only Noxus manages the data (Noxus bypasses
        // permission checks). These view grants are what let other parts of the
        // system read the lookups.
        $childIds = DB::table('menus')
            ->whereIn('route', array_column($children, 'route'))
            ->pluck('id');
        $roleIds = DB::table('roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($childIds as $menuId) {
                DB::table('role_menu')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_id' => $menuId],
                    [
                        'can_view' => 1,
                        'can_create' => 0,
                        'can_update' => 0,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }
}
