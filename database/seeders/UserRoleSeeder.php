<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usersRoles = [
            [
                'user_id' => 1,
                'role_id' => 1,
                'created_at' => now(),
            ],
            [
                'user_id' => 2,
                'role_id' => 2,
                'created_at' => now(),
            ]
        ];

        foreach ($usersRoles as $userRole) {
            DB::table('user_role')->insert($userRole);
        }
    }
}