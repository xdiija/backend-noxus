<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — adoptions + follow-ups, consistent with the
 * animals seeded by AnimalsTableSeeder (Bob = Adotado / Concluída, Mel =
 * Devolvido / Devolvida). Idempotent: keyed on
 * (organization_id, animal_id, adoption_date).
 */
class AdoptionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');

        $adoptions = [
            [
                'animal' => 'Bob',
                'adopter_cpf' => '12345678909', // Maria Silva
                'adoption_date' => '2026-06-01',
                'status' => 'Concluída',
                'agreement_signed' => true,
                'return_date' => null,
                'return_reason' => null,
                'notes' => 'Adotante já teve cães; casa com quintal.',
                'follow_ups' => [
                    [
                        'follow_up_date' => '2026-06-15',
                        'contact_method' => 'Telefone',
                        'status' => 'Contatado',
                        'notes' => 'Adaptação indo bem, animal tranquilo.',
                    ],
                    [
                        'follow_up_date' => '2026-07-15',
                        'contact_method' => 'WhatsApp',
                        'status' => 'Agendado',
                        'notes' => null,
                    ],
                ],
            ],
            [
                'animal' => 'Mel',
                'adopter_cpf' => '11144477735', // João Souza
                'adoption_date' => '2026-04-10',
                'status' => 'Devolvida',
                'agreement_signed' => true,
                'return_date' => '2026-05-05',
                'return_reason' => 'Alergia na família.',
                'notes' => null,
                'follow_ups' => [],
            ],
        ];

        foreach ($adoptions as $adoption) {
            $animalId = (int) DB::table('animals')
                ->where('organization_id', $org1)
                ->where('name', $adoption['animal'])
                ->value('id');

            DB::table('adoptions')->updateOrInsert(
                [
                    'organization_id' => $org1,
                    'animal_id' => $animalId,
                    'adoption_date' => $adoption['adoption_date'],
                ],
                [
                    'adopter_id' => (int) DB::table('adopters')
                        ->where('organization_id', $org1)
                        ->where('cpf', $adoption['adopter_cpf'])
                        ->value('id'),
                    'status_id' => (int) DB::table('adoption_statuses')->where('name', $adoption['status'])->value('id'),
                    'agreement_signed' => $adoption['agreement_signed'],
                    'return_date' => $adoption['return_date'],
                    'return_reason' => $adoption['return_reason'],
                    'notes' => $adoption['notes'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $adoptionId = (int) DB::table('adoptions')
                ->where('organization_id', $org1)
                ->where('animal_id', $animalId)
                ->where('adoption_date', $adoption['adoption_date'])
                ->value('id');

            foreach ($adoption['follow_ups'] as $followUp) {
                DB::table('adoption_follow_ups')->updateOrInsert(
                    ['adoption_id' => $adoptionId, 'follow_up_date' => $followUp['follow_up_date']],
                    [
                        'contact_method' => $followUp['contact_method'],
                        'status_id' => (int) DB::table('adoption_follow_up_statuses')->where('name', $followUp['status'])->value('id'),
                        'notes' => $followUp['notes'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }
}
