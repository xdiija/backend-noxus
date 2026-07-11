<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rescue extends Model
{
    use SoftDeletes;

    protected $table = 'rescues';

    protected $fillable = [
        'organization_id',
        'animal_id',
        'rescue_date',
        'location',
        'rescued_by',
        'condition',
        'description',
    ];

    protected $casts = [
        'rescue_date' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
