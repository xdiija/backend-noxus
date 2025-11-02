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
        $this->call(UsersTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(MenusTableSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(AccountsTableSeeder::class);
        $this->call(PaymentMethodsTableSeeder::class);
        $this->call(TransactionCategoriesTableSeeder::class);
        $this->call([SuppliersTableSeeder::class,]);
        $this->call([CustomersTableSeeder::class,]);
        $this->call([CostCentersTableSeeder::class,]);
    }
}
