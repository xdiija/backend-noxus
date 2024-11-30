<?php

use App\Http\Controllers\{
    AuthController,
    MenuController,
    UserController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('users', UserController::class);
Route::get('menus/getbyroles', [MenuController::class, 'getByRoles']);
Route::apiResource('menus', MenuController::class);

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});
