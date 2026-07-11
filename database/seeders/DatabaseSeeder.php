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
        $this->call(OrganizationsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(MenusTableSeeder::class);
        $this->call(ShelterMenusSeeder::class);
        $this->call(AnimalMenusSeeder::class);
        $this->call(AdoptionMenusSeeder::class);
        $this->call(AdminPermissionsSeeder::class);
        $this->call([SuppliersTableSeeder::class]);
        $this->call([CustomersTableSeeder::class]);

        // Padrões do sistema para o módulo de ONG / abrigo (idempotente).
        $this->call(DefaultLookupsSeeder::class);
    }
}
