<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermissions
{
    public function handle($request, Closure $next, $menuName, $permission)
    {   
        $isNoxusUser = PermissionService::isNoxusUser();
        $hasPermission = Auth::user()->hasPermission($menuName, $permission);

        if (!$isNoxusUser && !$hasPermission) {
            return response()->json([
                'error' => 'Usuário sem permissão para acessar este recurso!'
            ], 403);
        }
    
        return $next($request);
    }

}