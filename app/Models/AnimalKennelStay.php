<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalKennelStay extends Model
{
    protected $table = 'animal_kennel_stays';

    protected $fillable = [
        'animal_id',
        'kennel_id',
        'entry_date',
        'exit_date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function kennel(): BelongsTo
    {
        return $this->belongsTo(Kennel::class);
    }
}
