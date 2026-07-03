<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BreedsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $dogId = DB::table('species')->where('name', 'Cachorro')->value('id');
        $catId = DB::table('species')->where('name', 'Gato')->value('id');

        $breeds = [
            $dogId => [
                'SRD (Vira-lata)',
                'Poodle',
                'Pinscher',
                'Shih Tzu',
                'Yorkshire',
                'Lhasa Apso',
                'Labrador',
                'Golden Retriever',
                'Pastor Alemão',
                'Rottweiler',
                'Bulldog',
                'Pitbull',
                'Border Collie',
                'Dachshund (Salsicha)',
            ],
            $catId => [
                'SRD (Vira-lata)',
                'Siamês',
                'Persa',
                'Angorá',
                'Maine Coon',
                'Ragdoll',
                'Sphynx',
                'British Shorthair',
            ],
        ];

        foreach ($breeds as $speciesId => $names) {
            if (! $speciesId) {
                continue;
            }

            foreach ($names as $name) {
                DB::table('breeds')->updateOrInsert(
                    ['species_id' => $speciesId, 'name' => $name],
                    ['updated_at' => $now, 'created_at' => $now],
                );
            }
        }
    }
}
