<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdoptionFollowUp extends Model
{
    protected $table = 'adoption_follow_ups';

    protected $fillable = [
        'adoption_id',
        'follow_up_date',
        'contact_method',
        'status_id',
        'notes',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    public function adoption(): BelongsTo
    {
        return $this->belongsTo(Adoption::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AdoptionFollowUpStatus::class, 'status_id');
    }
}
