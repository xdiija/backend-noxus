<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $table = 'medications';

    protected $fillable = [
        'name',
        'description',
    ];
}
