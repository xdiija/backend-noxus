<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the "Financeiro" sidebar group with its "Doadores", "Doações" and
 * "Despesas" children (Module 6).
 *
 * Finance is organization-scoped operational data like the other shelter
 * resources, so the Admin role is granted full view/create/update (Noxus
 * bypasses the permission check). The donation-types / expense-categories
 * lookup routes reuse the `donations` / `expenses` grants, so no extra menus
 * are needed. Idempotent; depends on RolesTableSeeder.
 */
class FinanceMenusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Container menu (no route) — the "Financeiro" group.
        DB::table('menus')->updateOrInsert(
            ['name' => 'Financeiro', 'parent_id' => null],
            [
                'route' => null,
                'icon' => 'paid',
                'order' => 3,
                'status' => 1,
                'exclusive_noxus' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $parentId = DB::table('menus')
            ->whereNull('parent_id')
            ->where('name', 'Financeiro')
            ->value('id');

        $children = [
            ['name' => 'Doadores', 'route' => '/donors', 'icon' => 'favorite', 'order' => 1],
            ['name' => 'Doações', 'route' => '/donations', 'icon' => 'redeem', 'order' => 2],
            ['name' => 'Despesas', 'route' => '/expenses', 'icon' => 'receipt_long', 'order' => 3],
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
