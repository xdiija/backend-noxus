<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthRecord extends Model
{
    use SoftDeletes;

    protected $table = 'health_records';

    protected $fillable = [
        'organization_id',
        'animal_id',
        'type',
        'description',
        'event_date',
        'veterinarian',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
