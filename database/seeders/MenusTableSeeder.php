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
                'name' => 'Início',
                'route' => '/',
                'icon' => 'home',
                'parent_id' => null,
                'order' => 1
            ],
            [
                'name' => 'Logout',
                'route' => '/login',
                'icon' => 'exit_to_app',
                'parent_id' => null,
                'order' => 9999
            ],
            [
                'name' => 'Administrativo',
                'icon' => 'admin_panel_settings',
                'route' => '0',
                'parent_id' => null,
                'order' => 2
            ],
            [
                'name' => 'Usuários',
                'route' => '/users',
                'icon' => 'manage_accounts',
                'parent_id' => 3,
                'order' => 1
            ],
            [
                'name' => 'Perfis',
                'route' => '/roles',
                'icon' => 'groups',
                'parent_id' => 3,
                'order' => 2
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
