<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'route', 'parent_id', 'icon', 'order'];

    /**
     * Define the relationship between Menu and Role, a menu can be accessed by many roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu');
    }

    /**
     * Define a self-referential relationship for parent and child menus.
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}