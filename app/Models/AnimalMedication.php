<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A medication course for an animal (end_date null = ongoing). No soft deletes.
 */
class AnimalMedication extends Model
{
    protected $table = 'animal_medications';

    protected $fillable = [
        'animal_id',
        'medication_id',
        'dosage',
        'start_date',
        'end_date',
        'veterinarian',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
