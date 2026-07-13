<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vaccine dose administered to an animal. Pivot-like log — no soft deletes.
 */
class AnimalVaccine extends Model
{
    protected $table = 'animal_vaccines';

    protected $fillable = [
        'animal_id',
        'vaccine_id',
        'application_date',
        'dose',
    ];

    protected $casts = [
        'application_date' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }
}
