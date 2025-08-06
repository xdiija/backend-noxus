<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{

    public function handle($request, \Closure $next, ...$guards)
    {
        if ($request->hasCookie('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('token'));
        }

        return parent::handle($request, $next, ...$guards);
    }
    
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
