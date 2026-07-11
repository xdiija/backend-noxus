<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdoptionStatus extends Model
{
    protected $table = 'adoption_statuses';

    protected $fillable = [
        'organization_id',
        'name',
        'color',
        'sort_order',
        'is_default',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_default' => 'boolean',
    ];
}
