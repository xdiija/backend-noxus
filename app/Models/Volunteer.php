<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volunteer extends Model
{
    use SoftDeletes;

    protected $table = 'volunteers';

    protected $fillable = [
        'organization_id',
        'name',
        'phone',
        'email',
        'role',
        'status',
    ];
}
