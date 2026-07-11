<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adopter extends Model
{
    use SoftDeletes;

    protected $table = 'adopters';

    protected $fillable = [
        'organization_id',
        'name',
        'cpf',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'birth_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function adoptions(): HasMany
    {
        return $this->hasMany(Adoption::class);
    }
}
