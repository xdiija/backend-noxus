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
                'name' => 'Gabriel Massaia',
                'organization_id' => 2,
                'email' => 'gabriel@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 1,
            ],
            [
                'name' => 'Admin User',
                'organization_id' => 1,
                'email' => 'admin@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
                'role_id' => 2,
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        //User::factory()->count(10)->create();
    }
}