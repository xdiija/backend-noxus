<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — foster stays (placements), consistent with the
 * animals seeded by AnimalsTableSeeder: Frajola has an open stay (status kept
 * in sync as "Em Lar Temporário" there) and Thor has a closed, historical one
 * (his current status stays "Disponível" — nothing overrides it after the
 * stay closes, matching the real close() workflow). Idempotent: keyed on
 * (animal_id, entry_date).
 */
class AnimalFosterHomesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');
        $org2 = (int) DB::table('organizations')->where('tax_id', '99999999000199')->value('id');

        $stays = [
            [
                'organization_id' => $org1,
                'animal' => 'Thor',
                'foster_home' => 'Fernanda Costa',
                'entry_date' => '2026-04-01',
                'exit_date' => '2026-04-20',
            ],
            [
                'organization_id' => $org2,
                'animal' => 'Frajola',
                'foster_home' => 'Ricardo Alves',
                'entry_date' => '2026-07-01',
                'exit_date' => null,
            ],
        ];

        foreach ($stays as $stay) {
            $animalId = (int) DB::table('animals')
                ->where('organization_id', $stay['organization_id'])
                ->where('name', $stay['animal'])
                ->value('id');

            $fosterHomeId = (int) DB::table('foster_homes')
                ->where('organization_id', $stay['organization_id'])
                ->where('responsible_name', $stay['foster_home'])
                ->value('id');

            DB::table('animal_foster_homes')->updateOrInsert(
                ['animal_id' => $animalId, 'entry_date' => $stay['entry_date']],
                [
                    'foster_home_id' => $fosterHomeId,
                    'exit_date' => $stay['exit_date'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
