<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class CostCentersTableSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Marketing',
                'status' => 1
            ],
            [
                'name' => 'Comercial',
                'status' => 1
            ],
            [
                'name' => 'Marcenaria',
                'status' => 1
            ],
            [
                'name' => 'TI',
                'status' => 1
            ]
        ];

        foreach ($items as $item) {
            CostCenter::create($item);
        }
    }
}
