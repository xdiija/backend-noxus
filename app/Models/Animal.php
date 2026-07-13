<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use SoftDeletes;

    protected $table = 'animals';

    protected $fillable = [
        'organization_id',
        'name',
        'species_id',
        'breed_id',
        'sex',
        'estimated_birth_date',
        'birth_date_is_estimated',
        'size_id',
        'color',
        'microchip',
        'neutered',
        'neuter_date',
        'status_id',
        'notes',
    ];

    protected $casts = [
        'estimated_birth_date' => 'date',
        'birth_date_is_estimated' => 'boolean',
        'neutered' => 'boolean',
        'neuter_date' => 'date',
    ];

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(AnimalSize::class, 'size_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AnimalStatus::class, 'status_id');
    }

    public function rescue(): HasOne
    {
        return $this->hasOne(Rescue::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AnimalStatusHistory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(AnimalImage::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'animal_tags');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    /**
     * Applied vaccine doses (animal_vaccines), not the global vaccine catalog.
     */
    public function vaccines(): HasMany
    {
        return $this->hasMany(AnimalVaccine::class);
    }

    /**
     * Medication courses (animal_medications), not the global medication catalog.
     */
    public function medications(): HasMany
    {
        return $this->hasMany(AnimalMedication::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(Surgery::class);
    }

    public function fosterStays(): HasMany
    {
        return $this->hasMany(AnimalFosterHome::class);
    }

    /**
     * Age derived from estimated_birth_date (ADR-0006) — never stored, so it
     * can't go stale. Null when the birth date is unknown; the caller decides
     * how to render "~2 years" vs "2 years" from `is_estimated`.
     */
    public function getAgeAttribute(): ?array
    {
        if (! $this->estimated_birth_date) {
            return null;
        }

        $diff = $this->estimated_birth_date->diff(now());

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'is_estimated' => (bool) $this->birth_date_is_estimated,
        ];
    }
}
