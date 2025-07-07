<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public static array $statusNames = [
        1 => 'Pendente',
        2 => 'Pago',
        3 => 'Recusado',
        4 => 'Cancelado',
        5 => 'Em análise',
    ];

    public const STATUS_PENDING = 1;
    public const STATUS_PAID = 2;
    public const STATUS_REJECTED = 3;
    public const STATUS_CANCELED = 4;
    public const STATUS_UNDER_ANALYSIS = 5;

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

    public static function getStatusName(?int $status): ?string
    {
        return self::$statusNames[$status] ?? null;
    }
}
