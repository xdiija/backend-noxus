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
                'email' => 'djalma@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
            ],
            [
                'name' => 'Gabriel Massaia',
                'email' => 'gabriel@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('123456'),
                'status' => 1,
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        //User::factory()->count(10)->create();
    }
}