<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategoriesTableSeeder extends Seeder
{
    public function run(): void
    {
        $transactionCategories = [
            [
                'name' => 'Vendas de Produtos',
                'type' => 'income',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Vendas de Serviços',
                'type' => 'income',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Outros Ganhos',
                'type' => 'income',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Custos Operacionais',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Despesas Administrativas',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Despesas de Marketing e Vendas',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Despesas Financeiras',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Despesas Variáveis',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Impostos e Contribuições',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Ajuste de Saldo Saída',
                'type' => 'expense',
                'parent_id' => null,
                'status' => 1
            ],
            [
                'name' => 'Ajuste de Saldo Entrada',
                'type' => 'income',
                'parent_id' => null,
                'status' => 1
            ],
        ];

        foreach ($transactionCategories as $transactionCategorie) {
            TransactionCategory::create($transactionCategorie);
        }
    }
}
