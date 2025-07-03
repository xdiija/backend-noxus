<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Money;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'transaction_id',
        'account_id',
        'payment_method_id',
        'amount',
        'discount',
        'increase',
        'due_date',
        'payment_date',
        'status',
    ];

    public function setAmountAttribute(int $value): void
    {
        $this->attributes['amount'] = Money::fromIntToFloat($value);
    }

    public function setDiscountAttribute(?int $value): void
    {
        $this->attributes['discount'] = $value ? Money::fromIntToFloat($value) : 0;
    }

    public function setIncreaseAttribute(?int $value): void
    {
        $this->attributes['increase'] = $value ? Money::fromIntToFloat($value) : 0;
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
