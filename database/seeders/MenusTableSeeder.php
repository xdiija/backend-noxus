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
                'name' => 'Administrativo',
                'icon' => 'admin_panel_settings',
                'route' => null,
                'parent_id' => null,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                'name' => 'Usuários',
                'route' => '/users',
                'icon' => 'manage_accounts',
                'parent_id' => 1,
                'order' => 2,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                'name' => 'Perfis',
                'route' => '/roles',
                'icon' => 'groups',
                'parent_id' => 1,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                'name' => 'Menus',
                'route' => '/menus',
                'icon' => 'menu',
                'parent_id' => 1,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
