<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMovement extends Model
{
    protected $fillable = [
        'account_id',
        'payment_id',
        'type',
        'amount',
        'balance_after',
    ];

    /**
     * Get the account associated with the movement.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the payment associated with the movement (if any).
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
