<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationType extends Model
{
    protected $table = 'donation_types';

    protected $fillable = [
        'organization_id',
        'name',
    ];
}
