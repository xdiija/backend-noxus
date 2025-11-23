<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\MoneyHelper;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'balance',
        'status'
    ];

    public function adjustBalance(string $type, int $amount, Payment $payment, bool $isReversal = false): void
    {
        if (!in_array($type, ['income', 'expense'])) {
            throw new \InvalidArgumentException("Tipo de transação inválido.");
        }

        $amountToAdjust = $amount;
        if ($type === 'expense') {
            $amountToAdjust = -$amountToAdjust;
        }

        $balance =  MoneyHelper::fromFloatToInt($this->balance);
        $balance += $amountToAdjust;

        if ($balance < 0) {
            throw new \LogicException('Saldo insuficiente.');
        }

        $this->balance = MoneyHelper::fromIntToFloat($balance);
        $this->save();

        $this->registerMovement($payment->id, $amount, $type, $isReversal);
    }

    public function registerMovement(int $paymentId, int $amount, string $type, bool $isReversal): void
    {        
        AccountMovement::create([
            'account_id' => $this->id,
            'payment_id' => $paymentId,
            'type' => $type,
            'is_reversal' => $isReversal,
            'amount' => MoneyHelper::fromIntToFloat($amount),
            'balance_after' => $this->fresh()->balance,
        ]);
    }
}
