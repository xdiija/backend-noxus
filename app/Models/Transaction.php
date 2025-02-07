<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'amount',
        'due_date',
        'payment_date',
        'description',
        'category_id',
        'account_id',
    ];

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
