<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        \App\Models\User::factory()->create([
            'name' => 'Admin Teste',
            'email' => 'test@example.com',
            'status' => 1,
        ]);

        $this->call(RolesTableSeeder::class);
        $this->call(MenusTableSeeder::class);
    }
}
