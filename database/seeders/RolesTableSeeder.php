<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Desenvolvimento'],
            ['name' => 'Administrador'],
            ['name' => 'Vendedor'],
            ['name' => 'Financeiro']
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
