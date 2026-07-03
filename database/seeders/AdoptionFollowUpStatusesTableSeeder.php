<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdoptionFollowUpStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $statuses = [
            ['name' => 'Agendado',       'color' => '#3B82F6'],
            ['name' => 'Contatado',      'color' => '#10B981'],
            ['name' => 'Sem Resposta',   'color' => '#F59E0B'],
            ['name' => 'Alerta',         'color' => '#EF4444'],
            ['name' => 'Encerrado',      'color' => '#6B7280'],
        ];

        foreach ($statuses as $status) {
            DB::table('adoption_follow_up_statuses')->updateOrInsert(
                ['organization_id' => null, 'name' => $status['name']],
                ['color' => $status['color'], 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
