<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — foster homes per org, consistent with the stays
 * seeded by AnimalFosterHomesTableSeeder. Idempotent: keyed on
 * (organization_id, responsible_name).
 */
class FosterHomesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');
        $org2 = (int) DB::table('organizations')->where('tax_id', '99999999000199')->value('id');

        $fosterHomes = [
            [
                'organization_id' => $org1,
                'responsible_name' => 'Fernanda Costa',
                'phone' => '11973456789',
                'address' => 'Rua dos Girassóis, 123, São Paulo/SP',
                'capacity' => 2,
            ],
            [
                'organization_id' => $org2,
                'responsible_name' => 'Ricardo Alves',
                'phone' => '21993456789',
                'address' => 'Rua do Catete, 88, Rio de Janeiro/RJ',
                'capacity' => 1,
            ],
        ];

        foreach ($fosterHomes as $fosterHome) {
            DB::table('foster_homes')->updateOrInsert(
                ['organization_id' => $fosterHome['organization_id'], 'responsible_name' => $fosterHome['responsible_name']],
                array_merge($fosterHome, ['updated_at' => $now, 'created_at' => $now]),
            );
        }
    }
}
