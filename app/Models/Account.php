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

    public function adjustBalance(string $type, int $amount, Payment $payment, ?bool $isReversal = false): void
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

        $this->registerMovement($payment, $type, $isReversal);
    }

    public function registerMovement(?Payment $payment, string $type, bool $isReversal): void
    {
        AccountMovement::create([
            'account_id' => $this->id,
            'payment_id' => $payment?->id,
            'type' => $type,
            'is_reversal' => $isReversal,
            'amount' => $payment?->amount ?? 0,
            'balance_after' => $this->fresh()->balance,
        ]);
    }
}
