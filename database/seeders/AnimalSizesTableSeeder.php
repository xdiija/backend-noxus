<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalSizesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $sizes = [
            ['name' => 'Pequeno', 'sort_order' => 1],
            ['name' => 'Médio',   'sort_order' => 2],
            ['name' => 'Grande',  'sort_order' => 3],
            ['name' => 'Gigante', 'sort_order' => 4],
        ];

        foreach ($sizes as $size) {
            DB::table('animal_sizes')->updateOrInsert(
                ['organization_id' => null, 'name' => $size['name']],
                ['sort_order' => $size['sort_order'], 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
