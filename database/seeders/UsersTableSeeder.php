<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Djalma Leandro',
                'organization_id' => 1,
                'email' => 'djalma@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 1,
            ],
            [
                'name' => 'Juliana Cristina',
                'organization_id' => 1,
                'email' => 'juliana@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 2,
            ],
            [
                'name' => 'Felipe Dias',
                'organization_id' => 1,
                'email' => 'felipevol@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 5,
            ],
            [
                'name' => 'Djalma Leandro 2',
                'organization_id' => 2,
                'email' => 'djalma2@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 1,
            ],
            [
                'name' => 'Admin User',
                'organization_id' => 2,
                'email' => 'admin@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 2,
            ],
            [
                'name' => 'Guilherme Dias',
                'organization_id' => 1,
                'email' => 'guilhermevol@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 5,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        //User::factory()->count(10)->create();
    }
}