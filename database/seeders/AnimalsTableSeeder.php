<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — a few animals per org, with rescue intake, tags
 * and the initial status-history row. Idempotent: keyed on
 * (organization_id, name), safe to re-run.
 */
class AnimalsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = $this->orgId('12345678000199'); // ONG Teste
        $org2 = $this->orgId('99999999000199'); // Abrigo Dois

        $animals = [
            [
                'organization_id' => $org1,
                'name' => 'Thor',
                'species' => 'Cachorro',
                'breed' => 'Labrador',
                'sex' => 'M',
                'estimated_birth_date' => '2024-03-15',
                'size' => 'Grande',
                'color' => 'Caramelo',
                'microchip' => '900012345678901',
                'neutered' => false,
                'neuter_date' => null,
                'status' => 'Disponível',
                'notes' => 'Muito dócil, adora brincar com bola.',
                'tags' => ['Sociável com Crianças'],
                'rescue' => [
                    'rescue_date' => '2026-05-20 09:30:00',
                    'location' => 'Parque Ibirapuera, São Paulo',
                    'rescued_by' => 'Equipe ONG Teste',
                    'condition' => 'Desnutrido',
                    'description' => 'Encontrado sem coleira, magro, mas sociável.',
                ],
            ],
            [
                'organization_id' => $org1,
                'name' => 'Luna',
                'species' => 'Gato',
                'breed' => 'SRD (Vira-lata)',
                'sex' => 'F',
                'estimated_birth_date' => '2025-01-10',
                'size' => 'Pequeno',
                'color' => 'Preta e branca',
                'microchip' => null,
                'neutered' => false,
                'neuter_date' => null,
                'status' => 'Em Tratamento',
                'notes' => 'Em recuperação de ferimento na pata dianteira.',
                'tags' => ['Resgate Recente'],
                'rescue' => [
                    'rescue_date' => '2026-06-28 18:00:00',
                    'location' => 'Rua Augusta, centro',
                    'rescued_by' => 'Voluntária Paula',
                    'condition' => 'Ferimento na pata',
                    'description' => 'Resgatada após atropelamento leve.',
                ],
            ],
            [
                'organization_id' => $org1,
                'name' => 'Bob',
                'species' => 'Cachorro',
                'breed' => 'SRD (Vira-lata)',
                'sex' => 'M',
                'estimated_birth_date' => '2022-07-01',
                'size' => 'Médio',
                'color' => 'Preto',
                'microchip' => '900098765432109',
                // Castrado — consistente com a cirurgia de castração do seed
                // de surgeries (invariante do health.md).
                'neutered' => true,
                'neuter_date' => '2026-05-10',
                'status' => 'Adotado',
                'notes' => null,
                'tags' => ['Sociável com Outros Animais'],
                'rescue' => null,
            ],
            [
                'organization_id' => $org1,
                'name' => 'Mel',
                'species' => 'Cachorro',
                'breed' => 'Poodle',
                'sex' => 'F',
                'estimated_birth_date' => '2021-02-01',
                'size' => 'Pequeno',
                'color' => 'Branca',
                'microchip' => null,
                'neutered' => false,
                'neuter_date' => null,
                'status' => 'Devolvido',
                'notes' => 'Devolvida por alergia na família adotante.',
                'tags' => [],
                'rescue' => null,
            ],
            [
                'organization_id' => $org2,
                'name' => 'Frajola',
                'species' => 'Gato',
                'breed' => 'SRD (Vira-lata)',
                'sex' => 'M',
                'estimated_birth_date' => '2023-11-05',
                'size' => 'Médio',
                'color' => 'Preto e branco',
                'microchip' => null,
                'neutered' => false,
                'neuter_date' => null,
                // Em Lar Temporário — consistente com a estadia aberta do seed
                // de animal_foster_homes (AnimalFosterHomesTableSeeder).
                'status' => 'Em Lar Temporário',
                'notes' => null,
                'tags' => [],
                'rescue' => null,
            ],
        ];

        foreach ($animals as $animal) {
            $speciesId = (int) DB::table('species')->where('name', $animal['species'])->value('id');
            $statusId = (int) DB::table('animal_statuses')->where('name', $animal['status'])->value('id');

            DB::table('animals')->updateOrInsert(
                ['organization_id' => $animal['organization_id'], 'name' => $animal['name']],
                [
                    'species_id' => $speciesId,
                    'breed_id' => DB::table('breeds')->where('species_id', $speciesId)->where('name', $animal['breed'])->value('id'),
                    'sex' => $animal['sex'],
                    'estimated_birth_date' => $animal['estimated_birth_date'],
                    'birth_date_is_estimated' => true,
                    'size_id' => (int) DB::table('animal_sizes')->where('name', $animal['size'])->value('id'),
                    'color' => $animal['color'],
                    'microchip' => $animal['microchip'],
                    'neutered' => $animal['neutered'],
                    'neuter_date' => $animal['neuter_date'],
                    'status_id' => $statusId,
                    'notes' => $animal['notes'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $animalId = (int) DB::table('animals')
                ->where('organization_id', $animal['organization_id'])
                ->where('name', $animal['name'])
                ->value('id');

            // Initial status-history row (old = null), like AnimalService does.
            DB::table('animal_status_history')->updateOrInsert(
                ['animal_id' => $animalId, 'new_status_id' => $statusId],
                [
                    'old_status_id' => null,
                    'changed_by' => null,
                    'notes' => 'Registro inicial (seed)',
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            foreach ($animal['tags'] as $tag) {
                DB::table('animal_tags')->updateOrInsert([
                    'animal_id' => $animalId,
                    'tag_id' => (int) DB::table('tags')->where('name', $tag)->value('id'),
                ]);
            }

            if ($animal['rescue'] !== null) {
                DB::table('rescues')->updateOrInsert(
                    ['animal_id' => $animalId],
                    array_merge($animal['rescue'], [
                        'organization_id' => $animal['organization_id'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]),
                );
            }
        }
    }

    private function orgId(string $taxId): int
    {
        return (int) DB::table('organizations')->where('tax_id', $taxId)->value('id');
    }
}
