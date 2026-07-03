<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaccinesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $vaccines = [
            // Caninas
            'V8 (Óctupla)',
            'V10 (Déctupla)',
            'Giárdia',
            'Gripe Canina (Tosse dos Canis)',
            // Felinas
            'Tríplice Felina (V3)',
            'Quádrupla Felina (V4)',
            'Quíntupla Felina (V5)',
            // Comum
            'Antirrábica',
        ];

        foreach ($vaccines as $name) {
            DB::table('vaccines')->updateOrInsert(
                ['name' => $name],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
