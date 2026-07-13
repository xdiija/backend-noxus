<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — surgeries for the seeded animals. Bob's castration
 * matches his neutered/neuter_date in AnimalsTableSeeder (health.md
 * invariant). Idempotent: keyed on (animal_id, surgery_date, procedure).
 */
class SurgeriesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');

        $surgeries = [
            [
                'animal' => 'Bob',
                'surgery_date' => '2026-05-10',
                'procedure' => 'Castração',
                'veterinarian' => 'Dr. Paulo',
                'clinic' => 'Clínica Vet Vida',
                'cost' => 250.00,
                'notes' => 'Sem intercorrências.',
            ],
            [
                'animal' => 'Luna',
                'surgery_date' => '2026-06-29',
                'procedure' => 'Sutura de ferimento na pata',
                'veterinarian' => 'Dra. Ana',
                'clinic' => 'Clínica Vet Vida',
                'cost' => 180.00,
                'notes' => 'Retirada dos pontos prevista para 15/07.',
            ],
        ];

        foreach ($surgeries as $surgery) {
            DB::table('surgeries')->updateOrInsert(
                [
                    'animal_id' => (int) DB::table('animals')->where('organization_id', $org1)->where('name', $surgery['animal'])->value('id'),
                    'surgery_date' => $surgery['surgery_date'],
                    'procedure' => $surgery['procedure'],
                ],
                [
                    'organization_id' => $org1,
                    'veterinarian' => $surgery['veterinarian'],
                    'clinic' => $surgery['clinic'],
                    'cost' => $surgery['cost'],
                    'notes' => $surgery['notes'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
