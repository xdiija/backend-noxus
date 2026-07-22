<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenusTableSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                //id: 1
                'name' => 'Administrativo',
                'icon' => 'admin_panel_settings',
                'route' => null,
                'parent_id' => null,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0,
            ],
            [
                //id: 2
                'name' => 'Usuários',
                'route' => '/users',
                'icon' => 'manage_accounts',
                'parent_id' => 1,
                'order' => 2,
                'status' => 1,
                'exclusive_noxus' => 0,
            ],
            // Roles and menus are GLOBAL tables (no organization_id): a grant
            // edited by one org's admin would change permissions for every
            // organization. Until roles become org-scoped, managing them is
            // Noxus-only — exclusive_noxus menus are hidden from other users
            // and hasPermission() ignores any role_menu grant on them.
            [
                //id: 3
                'name' => 'Perfis',
                'route' => '/roles',
                'icon' => 'groups',
                'parent_id' => 1,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 1,
            ],
            [
                //id: 4
                'name' => 'Menus',
                'route' => '/menus',
                'icon' => 'menu',
                'parent_id' => 1,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 1,
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
