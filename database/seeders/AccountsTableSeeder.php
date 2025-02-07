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
                'balance' => 0,
                'status' => 1
            ],
        ];

        foreach ($Accounts as $Account) {
            Account::create($Account);
        }
    }
}
