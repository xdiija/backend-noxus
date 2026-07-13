<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = [
        'organization_id',
        'animal_id',
        'appointment_date',
        'veterinarian',
        'clinic',
        'purpose',
        'result',
        'next_visit',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'next_visit' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
