<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalSize extends Model
{
    protected $table = 'animal_sizes';

    protected $fillable = [
        'organization_id',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
