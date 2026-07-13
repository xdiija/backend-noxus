<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — medication courses for the seeded animals (one
 * finished, one ongoing with end_date null). Idempotent: keyed on
 * (animal_id, medication_id, start_date).
 */
class AnimalMedicationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');

        $courses = [
            [
                'animal' => 'Thor',
                'medication' => 'Vermífugo',
                'dosage' => '1 comprimido',
                'start_date' => '2026-05-22',
                'end_date' => '2026-05-24',
                'veterinarian' => 'Dra. Ana',
                'notes' => null,
            ],
            [
                'animal' => 'Luna',
                'medication' => 'Antibiótico',
                'dosage' => '250mg, 2x ao dia',
                'start_date' => '2026-06-29',
                'end_date' => null, // curso em andamento
                'veterinarian' => 'Dr. Paulo',
                'notes' => 'Manter até reavaliação do ferimento.',
            ],
        ];

        foreach ($courses as $course) {
            DB::table('animal_medications')->updateOrInsert(
                [
                    'animal_id' => (int) DB::table('animals')->where('organization_id', $org1)->where('name', $course['animal'])->value('id'),
                    'medication_id' => (int) DB::table('medications')->where('name', $course['medication'])->value('id'),
                    'start_date' => $course['start_date'],
                ],
                [
                    'dosage' => $course['dosage'],
                    'end_date' => $course['end_date'],
                    'veterinarian' => $course['veterinarian'],
                    'notes' => $course['notes'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
