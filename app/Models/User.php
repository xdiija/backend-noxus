<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }
    /**
     * Summary of hasPermission
     * @param mixed $menuName
     * @param mixed $permission view - create - update
     * @return bool
     */
    public function hasPermission($menuName, $permission): bool
    {
        $method = "User:hasPermission";
        $hashedSufix = md5("{$this->id}{$menuName}{$permission}");
        $cacheKey = "{$method}:{$hashedSufix}";
        $hasPermission = Cache::get($cacheKey);
        
        if ($hasPermission === null) {
            $hasPermission = $this->roles()
                ->where('roles.status', 1)
                ->join('role_menu', 'roles.id', '=', 'role_menu.role_id')
                ->join('menus', 'menus.id', '=', 'role_menu.menu_id')
                ->where('menus.name', $menuName)
                ->where("role_menu.can_{$permission}", 1)
                ->exists();

            Cache::put($cacheKey, $hasPermission, now()->addHour());
        }
        Redis::sadd('user_permissions_keys', $cacheKey);
        return $hasPermission;
    }

    public static function forgetUserPermissionsCache()
    {
        $keys = Redis::smembers('user_permissions_keys');
        foreach ($keys as $cacheKey) {
            Cache::forget($cacheKey);
        }
        Redis::del('user_permissions_keys');
    }
}
