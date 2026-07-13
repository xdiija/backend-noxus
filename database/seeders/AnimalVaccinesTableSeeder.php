<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — applied vaccine doses for the seeded animals.
 * Idempotent: keyed on (animal_id, vaccine_id, application_date).
 */
class AnimalVaccinesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');

        $doses = [
            ['animal' => 'Thor', 'vaccine' => 'V10 (Déctupla)', 'application_date' => '2026-05-25', 'dose' => '1ª dose'],
            ['animal' => 'Thor', 'vaccine' => 'Antirrábica', 'application_date' => '2026-06-01', 'dose' => 'Dose única'],
            ['animal' => 'Luna', 'vaccine' => 'Tríplice Felina (V3)', 'application_date' => '2026-07-02', 'dose' => '1ª dose'],
        ];

        foreach ($doses as $dose) {
            DB::table('animal_vaccines')->updateOrInsert(
                [
                    'animal_id' => (int) DB::table('animals')->where('organization_id', $org1)->where('name', $dose['animal'])->value('id'),
                    'vaccine_id' => (int) DB::table('vaccines')->where('name', $dose['vaccine'])->value('id'),
                    'application_date' => $dose['application_date'],
                ],
                [
                    'dose' => $dose['dose'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
