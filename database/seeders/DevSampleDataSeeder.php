<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Development-only sample data for the shelter module: a few animals (with
 * rescue, tags, status history), adopters, adoptions (+ follow-ups), clinical
 * history (Module 4), and volunteers/foster homes/stays (Module 5). Depends on
 * OrganizationsTableSeeder and DefaultLookupsSeeder having run first. Every
 * seeder here is idempotent (updateOrInsert on natural keys), so re-seeding
 * never duplicates rows.
 */
class DevSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AnimalsTableSeeder::class,
            AdoptersTableSeeder::class,
            AdoptionsTableSeeder::class,
            HealthRecordsTableSeeder::class,
            AnimalVaccinesTableSeeder::class,
            AnimalMedicationsTableSeeder::class,
            AppointmentsTableSeeder::class,
            SurgeriesTableSeeder::class,
            VolunteersTableSeeder::class,
            FosterHomesTableSeeder::class,
            AnimalFosterHomesTableSeeder::class,
        ]);
    }
}
