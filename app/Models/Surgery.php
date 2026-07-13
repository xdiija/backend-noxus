<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Surgery extends Model
{
    use SoftDeletes;

    protected $table = 'surgeries';

    protected $fillable = [
        'organization_id',
        'animal_id',
        'surgery_date',
        'procedure',
        'veterinarian',
        'clinic',
        'cost',
        'notes',
    ];

    protected $casts = [
        'surgery_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
