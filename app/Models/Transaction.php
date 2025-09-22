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
        'description',
        'category_id',
        'payment_type',
        'payment_count'
    ];

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id');
    }
}
