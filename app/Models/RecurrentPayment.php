<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurrentPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recurrent_payments';

    protected $fillable = [
        'transaction_id',
        'amount',
        'account_id',
        'payment_method_id',
        'interval',
        'start_date',
        'next_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_date'  => 'date',
        'end_date'   => 'date',
    ];

    /**
     * A recurrent payment belongs to a transaction.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
