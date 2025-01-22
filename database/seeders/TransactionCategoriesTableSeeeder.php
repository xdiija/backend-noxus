<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionCategoriesTableSeeeder extends Seeder
{
    public function run()
    {
        DB::table('transaction_categories')->insert([
            ['name' => 'Ajuste de Saldo', 'type' => 'income', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Venda de Produtos', 'type' => 'income', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Venda de Serviços', 'type' => 'income', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Ajuste de Saldo', 'type' => 'expense', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Alimentação', 'type' => 'expense', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Matéria Prima', 'type' => 'expense', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Prestação de Serviços', 'type' => 'expense', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transporte e Entrega', 'type' => 'expense', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()]
        ]);
    }
}
