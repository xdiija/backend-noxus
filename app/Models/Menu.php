<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'route', 'parent_id', 'icon', 'order', 'status', 'exclusive_noxus'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu')->withTimestamps();
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}