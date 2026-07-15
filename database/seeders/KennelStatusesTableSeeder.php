<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KennelStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $statuses = [
            ['name' => 'Disponível',    'color' => '#22C55E', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Em Manutenção', 'color' => '#F59E0B', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Isolamento',    'color' => '#EF4444', 'sort_order' => 3, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            DB::table('kennel_statuses')->updateOrInsert(
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
