<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountsTableSeeder extends Seeder
{
    public function run(): void
    {
        $Accounts = [
            [
                'name' => 'Caixa',
                'type' => 'cash',
                'balance' => 50,
                'status' => 1
            ],
            [
                'name' => 'Bradesco 1',
                'type' => 'bank',
                'balance' => 1000,
                'status' => 1
            ],
        ];

        foreach ($Accounts as $Account) {
            Account::create($Account);
        }
    }
}
