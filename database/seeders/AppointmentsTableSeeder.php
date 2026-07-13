<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — vet appointments for the seeded animals (one
 * attended with result, one upcoming). Idempotent: keyed on
 * (animal_id, appointment_date).
 */
class AppointmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');

        $appointments = [
            [
                'animal' => 'Luna',
                'appointment_date' => '2026-06-29 10:00:00',
                'veterinarian' => 'Dra. Ana',
                'clinic' => 'Clínica Vet Vida',
                'purpose' => 'Avaliação do ferimento na pata',
                'result' => 'Sutura realizada; curativo trocado, prescrito antibiótico.',
                'next_visit' => '2026-07-15',
            ],
            [
                'animal' => 'Thor',
                'appointment_date' => '2026-07-20 15:00:00',
                'veterinarian' => 'Dr. Paulo',
                'clinic' => 'Clínica Vet Vida',
                'purpose' => 'Consulta de rotina',
                'result' => null,
                'next_visit' => null,
            ],
        ];

        foreach ($appointments as $appointment) {
            DB::table('appointments')->updateOrInsert(
                [
                    'animal_id' => (int) DB::table('animals')->where('organization_id', $org1)->where('name', $appointment['animal'])->value('id'),
                    'appointment_date' => $appointment['appointment_date'],
                ],
                [
                    'organization_id' => $org1,
                    'veterinarian' => $appointment['veterinarian'],
                    'clinic' => $appointment['clinic'],
                    'purpose' => $appointment['purpose'],
                    'result' => $appointment['result'],
                    'next_visit' => $appointment['next_visit'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
