<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Services\PermissionService;

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

        if (auth()->user()->status != Status::ACTIVE->value) {
            return response()->json(['error' => 'inactive_user', 'message' => 'Usuário inativo.'], 401);
        }

        $authUser = auth()->user();
        $user = [
            'name' => $authUser->name,
            'organization' => $authUser->organization ? [
                'id' => $authUser->organization->id,
                'name' => $authUser->organization->name,
            ] : null,
        ];
        $menus = $this->menuController->getByRoles()->resolve();
        $permissions = PermissionService::permissionsMap();

        return $this->respondWithToken($token, $user, $menus, $permissions);
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
            'role' => $user->role,
            'organization' => $user->organization ? [
                'id' => $user->organization->id,
                'name' => $user->organization->name,
            ] : null,
            'permissions' => PermissionService::permissionsMap(),
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
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user = [], $menus = [], $permissions = [])
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
                'permissions' => $permissions,
                'expires_in' => auth()->factory()->getTTL() * 60,
            ])
            ->withCookie($cookie);
    }
}
