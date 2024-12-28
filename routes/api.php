<?php

use App\Http\Controllers\{
    AuthController,
    MenuController,
    RoleController,
    UserController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('login', 'login')->name('login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('me', 'me')->name('me');
});

Route::prefix('users')->middleware('auth')->controller(UserController::class)->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/', 'index')->middleware('check.permissions:Usuários,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Usuários,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Usuários,update');
    Route::post('/', 'store')->middleware('check.permissions:Usuários,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Usuários,update');
});

Route::prefix('roles')->middleware('auth')->controller(RoleController::class)->group(function () {
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:Perfis,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Perfis,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Perfis,update');
    Route::post('/', 'store')->middleware('check.permissions:Perfis,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Perfis,update');
});

Route::prefix('menus')->middleware('auth')->controller(MenuController::class)->group(function () {
    Route::get('/getbyroles', 'getByRoles');
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:Menus,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Menus,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Menus,update');
    Route::post('/', 'store')->middleware('check.permissions:Menus,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Menus,update');
});