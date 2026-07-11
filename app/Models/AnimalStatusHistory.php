<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only log of an animal's status transitions (no soft deletes; see
 * data-model/conventions.md). One row is written whenever animals.status_id
 * changes; corrections are new rows, never updates.
 */
class AnimalStatusHistory extends Model
{
    protected $table = 'animal_status_history';

    protected $fillable = [
        'animal_id',
        'old_status_id',
        'new_status_id',
        'changed_by',
        'notes',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(AnimalStatus::class, 'old_status_id');
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(AnimalStatus::class, 'new_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
