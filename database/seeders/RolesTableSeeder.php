<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Desenvolvimento',
                'status' => 1
            ],
            [
                'name' => 'Administrador',
                'status' => 1
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
