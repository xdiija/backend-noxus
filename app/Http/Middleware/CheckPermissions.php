<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermissions
{
    public function handle($request, Closure $next, $menuName, $permission)
    {
        if (!Auth::user()->hasPermission($menuName, $permission)) {
            return response()->json(['error' => 'Usuário sem permissão para acessar este recurso!'], 403);
        }
    
        return $next($request);
    }
}