<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermissions
{
    public function handle($request, Closure $next, $menuKey, $permission)
    {
        $isNoxusUser = PermissionService::isNoxusUser();
        $hasPermission = Auth::user()->hasPermission($menuKey, $permission);

        if (!$isNoxusUser && !$hasPermission) {
            return response()->json([
                'error' => 'Usuário sem permissão para acessar este recurso!'
            ], 403);
        }
    
        return $next($request);
    }

}