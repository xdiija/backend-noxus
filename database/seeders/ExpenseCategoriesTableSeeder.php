<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // organization_id = null => padrão do sistema
        $categories = [
            'Veterinário',
            'Alimentação',
            'Medicamentos',
            'Suprimentos',
            'Transporte',
            'Contas / Utilidades',
            'Outros',
        ];

        foreach ($categories as $name) {
            DB::table('expense_categories')->updateOrInsert(
                ['organization_id' => null, 'name' => $name],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
