<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('customers')->insert([
            [
                'name' => 'João Silva',
                'email' => 'joao.silva@example.com',
                'cpf' => '12345678901',
                'phone_1' => '11999999999',
                'phone_2' => null,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maria Oliveira',
                'email' => 'maria.oliveira@example.com',
                'cpf' => '98765432100',
                'phone_1' => '11888888888',
                'phone_2' => '11777777777',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
