<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema, disponível para todas as organizações
        $statuses = [
            ['name' => 'Em Tratamento',       'color' => '#F59E0B', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Disponível',          'color' => '#10B981', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Em Lar Temporário',   'color' => '#3B82F6', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Reservado',           'color' => '#8B5CF6', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Adotado',             'color' => '#22C55E', 'sort_order' => 5, 'is_default' => false],
            ['name' => 'Devolvido',           'color' => '#EF4444', 'sort_order' => 6, 'is_default' => false],
            ['name' => 'Óbito',               'color' => '#6B7280', 'sort_order' => 7, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            DB::table('animal_statuses')->updateOrInsert(
                ['organization_id' => null, 'name' => $status['name']],
                [
                    'color' => $status['color'],
                    'sort_order' => $status['sort_order'],
                    'is_default' => $status['is_default'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
