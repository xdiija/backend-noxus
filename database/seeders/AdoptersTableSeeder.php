<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development sample data — adopters per org. Idempotent: keyed on
 * (organization_id, cpf), matching the table's unique index.
 */
class AdoptersTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $org1 = (int) DB::table('organizations')->where('tax_id', '12345678000199')->value('id');
        $org2 = (int) DB::table('organizations')->where('tax_id', '99999999000199')->value('id');

        $adopters = [
            [
                'organization_id' => $org1,
                'name' => 'Maria Silva',
                'cpf' => '12345678909',
                'phone' => '11987654321',
                'email' => 'maria.silva@example.com',
                'address' => 'Rua das Flores, 45',
                'city' => 'São Paulo',
                'state' => 'SP',
                'birth_date' => '1988-04-12',
            ],
            [
                'organization_id' => $org1,
                'name' => 'João Souza',
                'cpf' => '11144477735',
                'phone' => '11976543210',
                'email' => 'joao.souza@example.com',
                'address' => 'Avenida Paulista, 1000, ap 52',
                'city' => 'São Paulo',
                'state' => 'SP',
                'birth_date' => '1975-09-30',
            ],
            [
                'organization_id' => $org2,
                'name' => 'Carla Lima',
                'cpf' => '12345678909',
                'phone' => '21998877665',
                'email' => 'carla.lima@example.com',
                'address' => 'Rua do Catete, 200',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'birth_date' => '1992-01-20',
            ],
        ];

        foreach ($adopters as $adopter) {
            DB::table('adopters')->updateOrInsert(
                ['organization_id' => $adopter['organization_id'], 'cpf' => $adopter['cpf']],
                array_merge($adopter, ['updated_at' => $now, 'created_at' => $now]),
            );
        }
    }
}
