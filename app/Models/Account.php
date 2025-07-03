<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Money;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'balance',
        'status'
    ];

    public function adjustBalance(string $type, int $amount): void
    {
        if (!in_array($type, ['income', 'expense'])) {
            throw new \InvalidArgumentException("Tipo de transação inválido.");
        }

        if ($type === 'expense') {
            $amount = -$amount;
        }

        $balance =  Money::fromFloatToInt($this->balance);
        $balance += $amount;

        if ($balance < 0) {
            throw new \LogicException('Saldo insuficiente.');
        }

        $this->balance = Money::fromIntToFloat($balance);
        $this->save();
    }
}
