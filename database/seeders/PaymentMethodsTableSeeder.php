<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsTableSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Dinheiro',
                'status' => 1
            ],
            [
                'name' => 'Pix',
                'status' => 1
            ],
            [
                'name' => 'Cartão de Débito',
                'status' => 1
            ],
            [
                'name' => 'Cartão de Crédito',
                'status' => 1
            ],
            [
                'name' => 'Boleto',
                'status' => 1
            ],
            [
                'name' => 'Cheque',
                'status' => 1
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::create($paymentMethod);
        }
    }
}
