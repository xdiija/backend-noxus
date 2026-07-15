<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KennelStatus extends Model
{
    protected $table = 'kennel_statuses';

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
