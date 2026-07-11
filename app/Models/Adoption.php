<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adoption extends Model
{
    use SoftDeletes;

    protected $table = 'adoptions';

    protected $fillable = [
        'organization_id',
        'animal_id',
        'adopter_id',
        'adoption_date',
        'status_id',
        'agreement_signed',
        'return_date',
        'return_reason',
        'notes',
    ];

    protected $casts = [
        'adoption_date' => 'date',
        'return_date' => 'date',
        'agreement_signed' => 'boolean',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function adopter(): BelongsTo
    {
        return $this->belongsTo(Adopter::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AdoptionStatus::class, 'status_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(AdoptionFollowUp::class);
    }
}
