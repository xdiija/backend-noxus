<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpeciesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $species = ['Cachorro', 'Gato'];

        foreach ($species as $name) {
            DB::table('species')->updateOrInsert(
                ['name' => $name],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
