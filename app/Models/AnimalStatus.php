<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalStatus extends Model
{
    use SoftDeletes;

    protected $table = 'animal_statuses';

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
