<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Breed extends Model
{
    protected $table = 'breeds';

    protected $fillable = [
        'organization_id',
        'species_id',
        'name',
    ];

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }
}
