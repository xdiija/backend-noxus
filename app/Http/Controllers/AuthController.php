<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Helpers\StatusHelper;

class AuthController extends Controller
{
    protected MenuController $menuController;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(MenuController $menuController)
    {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->menuController = $menuController;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        if(auth()->user()->status != StatusHelper::ACTIVE){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = ['name' => auth()->user()->name];
        $menus = $this->menuController->getByRoles()->resolve();
        return $this->respondWithToken($token, $user, $menus);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles,
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $cookie = cookie()->forget('token');
        
        auth()->logout();
        
        return response()
            ->json(['message' => 'Successfully logged out'])
            ->withCookie($cookie);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user = [], $menus = [])
    {
        $cookie = cookie(
            'token', 
            $token, 
            auth()->factory()->getTTL(), // minutes
            null, 
            null, 
            true,  // Secure
            true,  // HttpOnly
            false, 
            'None'
        );

        return response()
            ->json([
                'user' => $user,
                'menus' => $menus,
                'expires_in' => auth()->factory()->getTTL() * 60
            ])
            ->withCookie($cookie);
    }

}