<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $organizations = [
            [
                'tax_id' => '12345678000199',
                'name' => 'ONG Teste',
                'legal_name' => 'Organização Teste de Proteção Animal',
                'email' => 'contato@ongteste.org',
                'phone' => '11999990000',
                'website' => 'https://ongteste.org',
                'address' => 'Rua dos Animais, 100',
                'city' => 'São Paulo',
                'state' => 'SP',
            ],
            [
                'tax_id' => '99999999000199',
                'name' => 'Abrigo Dois',
                'legal_name' => 'Abrigo Dois Proteção Animal',
                'email' => 'contato@abrigodois.org',
                'phone' => '21988880000',
                'website' => 'https://abrigodois.org',
                'address' => 'Avenida dos Bichos, 200',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
            ],
        ];

        foreach ($organizations as $organization) {
            // Idempotent: keyed on the (unique) tax_id so re-seeding won't duplicate.
            DB::table('organizations')->updateOrInsert(
                ['tax_id' => $organization['tax_id']],
                array_merge($organization, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]),
            );
        }
    }
}
