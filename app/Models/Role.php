<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    public const NOXUS_ROLE = 1;
    public const ADMIN_ROLE = 2;

    protected $fillable = ['name', 'status'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu')->withTimestamps();
    }
}
