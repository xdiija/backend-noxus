<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $medications = [
            ['name' => 'Vermífugo',                'description' => 'Tratamento contra vermes e parasitas intestinais.'],
            ['name' => 'Antipulgas / Carrapaticida', 'description' => 'Controle de pulgas e carrapatos.'],
            ['name' => 'Antibiótico',              'description' => 'Tratamento de infecções bacterianas.'],
            ['name' => 'Anti-inflamatório',        'description' => 'Redução de inflamação e dor.'],
            ['name' => 'Analgésico',               'description' => 'Controle da dor.'],
            ['name' => 'Antiparasitário',          'description' => 'Tratamento de parasitas externos e internos.'],
        ];

        foreach ($medications as $medication) {
            DB::table('medications')->updateOrInsert(
                ['name' => $medication['name']],
                ['description' => $medication['description'], 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
