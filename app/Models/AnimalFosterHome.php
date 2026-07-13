<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalFosterHome extends Model
{
    protected $table = 'animal_foster_homes';

    protected $fillable = [
        'animal_id',
        'foster_home_id',
        'entry_date',
        'exit_date',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function fosterHome(): BelongsTo
    {
        return $this->belongsTo(FosterHome::class);
    }
}
