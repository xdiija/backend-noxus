<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donor extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'donors';

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'notes',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
