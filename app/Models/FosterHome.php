<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FosterHome extends Model
{
    use SoftDeletes;

    protected $table = 'foster_homes';

    protected $fillable = [
        'organization_id',
        'responsible_name',
        'phone',
        'address',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(AnimalFosterHome::class);
    }

    /**
     * Stays currently in progress (exit_date IS NULL) — what counts against
     * `capacity`.
     */
    public function openPlacements(): HasMany
    {
        return $this->placements()->whereNull('exit_date');
    }
}
