<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — generic clinical events for the seeded animals.
 * Idempotent: keyed on (animal_id, type, event_date).
 */
class HealthRecordsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');

        $records = [
            [
                'animal' => 'Thor',
                'type' => 'Vermifugação',
                'description' => 'Dose única de vermífugo na chegada.',
                'event_date' => '2026-05-22',
                'veterinarian' => 'Dra. Ana',
                'notes' => null,
            ],
            [
                'animal' => 'Luna',
                'type' => 'Exame',
                'description' => 'Hemograma completo após o resgate.',
                'event_date' => '2026-06-29',
                'veterinarian' => 'Dr. Paulo',
                'notes' => 'Leve anemia; repetir em 30 dias.',
            ],
            [
                'animal' => 'Bob',
                'type' => 'Observação',
                'description' => 'Avaliação comportamental pré-adoção.',
                'event_date' => '2026-05-01',
                'veterinarian' => null,
                'notes' => 'Sociável, sem sinais de agressividade.',
            ],
        ];

        foreach ($records as $record) {
            $animalId = (int) DB::table('animals')
                ->where('organization_id', $org1)
                ->where('name', $record['animal'])
                ->value('id');

            DB::table('health_records')->updateOrInsert(
                ['animal_id' => $animalId, 'type' => $record['type'], 'event_date' => $record['event_date']],
                [
                    'organization_id' => $org1,
                    'description' => $record['description'],
                    'veterinarian' => $record['veterinarian'],
                    'notes' => $record['notes'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
