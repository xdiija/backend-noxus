<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $tags = [
            'Necessidades Especiais',
            'Idoso',
            'Dupla Inseparável',
            'Sociável com Crianças',
            'Sociável com Outros Animais',
            'Resgate Recente',
        ];

        foreach ($tags as $name) {
            DB::table('tags')->updateOrInsert(
                ['organization_id' => null, 'name' => $name],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
