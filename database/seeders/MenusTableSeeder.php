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
                'exclusive_noxus' => 0
            ],
            [
                //id: 2
                'name' => 'Usuários',
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
                'route' => '/customers',
                'icon' => 'person',
                'parent_id' => 5,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 7
                'name' => 'Financeiro',
                'icon' => 'attach_money',
                'route' => null,
                'parent_id' => null,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 8
                'name' => 'Contas',
                'route' => '/accounts',
                'icon' => 'account_balance_wallet',
                'parent_id' => 7,
                'order' => 3,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 9
                'name' => 'Categorias de Movimentações',
                'route' => '/transaction-categories',
                'icon' => 'category',
                'parent_id' => 7,
                'order' => 3,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 10
                'name' => 'Transações',
                'route' => '/transactions',
                'icon' => 'point_of_sale',
                'parent_id' => 7,
                'order' => 1,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 11
                'name' => 'Lançamentos',
                'route' => '/payments',
                'icon' => 'payments',
                'parent_id' => 7,
                'order' => 2,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
            [
                //id: 12
                'name' => 'Métodos de Pagamento',
                'route' => '/payment-methods',
                'icon' => 'credit_card',
                'parent_id' => 7,
                'order' => 3,
                'status' => 1,
                'exclusive_noxus' => 0
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
