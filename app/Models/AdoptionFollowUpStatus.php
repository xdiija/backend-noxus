<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdoptionFollowUpStatus extends Model
{
    protected $table = 'adoption_follow_up_statuses';

    protected $fillable = [
        'organization_id',
        'name',
        'color',
    ];
}
