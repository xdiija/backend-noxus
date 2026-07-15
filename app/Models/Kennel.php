<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kennel extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'kennels';

    protected $fillable = [
        'organization_id',
        'name',
        'capacity',
        'size_id',
        'status_id',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function size(): BelongsTo
    {
        return $this->belongsTo(AnimalSize::class, 'size_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(KennelStatus::class, 'status_id');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(AnimalKennelStay::class);
    }

    /**
     * Stays currently in progress (exit_date IS NULL) — what counts against
     * `capacity`. Occupancy is always derived, never stored.
     */
    public function openStays(): HasMany
    {
        return $this->stays()->whereNull('exit_date');
    }
}
