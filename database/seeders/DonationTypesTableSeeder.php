<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonationTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $types = ['Dinheiro', 'Ração / Alimentos', 'Medicamentos', 'Suprimentos', 'Serviços'];

        foreach ($types as $name) {
            DB::table('donation_types')->updateOrInsert(
                ['organization_id' => null, 'name' => $name],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
