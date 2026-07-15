<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultLookupsSeeder extends Seeder
{
    /**
     * Dados de referência / padrões do sistema.
     * Idempotente: seguro para rodar em todo deploy.
     */
    public function run(): void
    {
        $this->call([
            SpeciesTableSeeder::class,
            BreedsTableSeeder::class,
            AnimalStatusesTableSeeder::class,
            AnimalSizesTableSeeder::class,
            AdoptionStatusesTableSeeder::class,
            AdoptionFollowUpStatusesTableSeeder::class,
            KennelStatusesTableSeeder::class,
            DonationTypesTableSeeder::class,
            ExpenseCategoriesTableSeeder::class,
            VaccinesTableSeeder::class,
            MedicationsTableSeeder::class,
            TagsTableSeeder::class,
        ]);
    }
}
