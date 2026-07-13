<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — volunteers per org. Idempotent: keyed on
 * (organization_id, name).
 */
class VolunteersTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');
        $org2 = (int) DB::table('organizations')->where('tax_id', '99999999000199')->value('id');

        $volunteers = [
            [
                'organization_id' => $org1,
                // Mesma voluntária citada no resgate da Luna (AnimalsTableSeeder).
                'name' => 'Paula Nunes',
                'phone' => '11991234567',
                'email' => 'paula.nunes@example.com',
                'role' => 'Resgate',
                'status' => 1,
            ],
            [
                'organization_id' => $org1,
                // Inativo — exemplo do toggle de status na listagem.
                'name' => 'Marcos Vieira',
                'phone' => '11982345678',
                'email' => 'marcos.vieira@example.com',
                'role' => 'Transporte',
                'status' => 0,
            ],
            [
                'organization_id' => $org1,
                // Também responsável pelo lar temporário do seed de foster_homes
                // (FosterHomesTableSeeder).
                'name' => 'Fernanda Costa',
                'phone' => '11973456789',
                'email' => null,
                'role' => 'Lar temporário',
                'status' => 1,
            ],
            [
                'organization_id' => $org2,
                'name' => 'Juliana Prado',
                'phone' => '21994567890',
                'email' => 'juliana.prado@example.com',
                'role' => 'Lar temporário',
                'status' => 1,
            ],
        ];

        foreach ($volunteers as $volunteer) {
            DB::table('volunteers')->updateOrInsert(
                ['organization_id' => $volunteer['organization_id'], 'name' => $volunteer['name']],
                array_merge($volunteer, ['updated_at' => $now, 'created_at' => $now]),
            );
        }
    }
}
