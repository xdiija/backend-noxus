<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuppliersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('suppliers')->insert([
            [
                'nome_fantasia' => 'Comercial Silva',
                'razao_social' => 'Comercial Silva LTDA',
                'inscricao_estadual' => '123456789',
                'email' => 'contato@comercialsilva.com',
                'cnpj' => '12345678000195',
                'phone_1' => '(11) 99999-1111',
                'phone_2' => '(11) 98888-2222',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome_fantasia' => 'Distribuidora Central',
                'razao_social' => 'Distribuidora Central EIRELI',
                'inscricao_estadual' => null,
                'email' => 'vendas@dcentral.com.br',
                'cnpj' => '98765432000109',
                'phone_1' => '(21) 97777-3333',
                'phone_2' => null,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
