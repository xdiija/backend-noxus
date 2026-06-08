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
                'key' => 'administrative',
                'icon' => 'admin_panel_settings',
                'route' => null,
                'parent_id' => null,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 2
                'name' => 'Usuários',
                'key' => 'users',
                'route' => '/users',
                'icon' => 'manage_accounts',
                'parent_id' => 1,
                'order' => 2,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 3
                'name' => 'Perfis',
                'key' => 'roles',
                'route' => '/roles',
                'icon' => 'groups',
                'parent_id' => 1,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 4
                'name' => 'Menus',
                'key' => 'menus',
                'route' => '/menus',
                'icon' => 'menu',
                'parent_id' => 1,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 5
                'name' => 'Comercial',
                'key' => 'commercial',
                'icon' => 'business_center',
                'route' => null,
                'parent_id' => null,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 6
                'name' => 'Clientes',
                'key' => 'customers',
                'route' => '/customers',
                'icon' => 'person',
                'parent_id' => 5,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 7
                'name' => 'Fornecedores',
                'key' => 'suppliers',
                'route' => '/suppliers',
                'icon' => 'person_4',
                'parent_id' => 5,
                'order' => 3,
                'status' => 1,
                'exclusive_noxus' => 0
            ]
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
