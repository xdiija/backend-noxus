<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdoptionStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $statuses = [
            ['name' => 'Pendente',  'color' => '#F59E0B', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Concluída', 'color' => '#22C55E', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Devolvida', 'color' => '#EF4444', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Cancelada', 'color' => '#6B7280', 'sort_order' => 4, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            DB::table('adoption_statuses')->updateOrInsert(
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
