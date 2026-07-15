<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'donations';

    protected $fillable = [
        'organization_id',
        'donor_id',
        'donor_name',
        'donor_email',
        'donor_phone',
        'donation_type_id',
        'amount',
        'donation_date',
        'notes',
    ];

    protected $casts = [
        'donation_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function donationType(): BelongsTo
    {
        return $this->belongsTo(DonationType::class);
    }
}
