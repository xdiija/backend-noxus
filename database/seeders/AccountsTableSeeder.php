<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountsTableSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name'       => 'Caixa',
                'type'       => 'cash',
                'bank_id'    => null,
                'agency'     => null,
                'number'     => null,
                'phone'      => null,
                'balance'    => 50,
                'is_default' => true,
                'status'     => true,
            ],
            [
                'name'       => 'Bradesco 1',
                'type'       => 'bank',
                'bank_id'    => 1,
                'agency'     => '1233',
                'number'     => '5362665',
                'phone'      => '4733763166',
                'balance'    => 1000,
                'is_default' => false,
                'status'     => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}
