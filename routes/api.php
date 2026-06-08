<?php

use App\Http\Controllers\{
    AuthController,
    CustomerController,
    SupplierController,
    MenuController,
    RoleController,
    UserController,
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
    Route::get('/', 'index')->middleware('check.permissions:users,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:users,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:users,update');
    Route::post('/', 'store')->middleware('check.permissions:users,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:users,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:users,update');
    Route::put('/{id}/change-password', 'changePassword');
});

Route::prefix('roles')->middleware('auth')->controller(RoleController::class)->group(function () {
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:roles,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:roles,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:roles,update');
    Route::post('/', 'store')->middleware('check.permissions:roles,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:roles,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:roles,update');
});

Route::prefix('menus')->middleware('auth')->controller(MenuController::class)->group(function () {
    Route::get('/getbyroles', 'getByRoles');
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:menus,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:menus,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:menus,update');
    Route::post('/', 'store')->middleware('check.permissions:menus,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:menus,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:menus,update');
});

Route::prefix('customers')->middleware('auth')->controller(CustomerController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:customers,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:customers,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:customers,update');
    Route::post('/', 'store')->middleware('check.permissions:customers,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:customers,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:customers,update');
});

Route::prefix('suppliers')->middleware('auth')->controller(SupplierController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:suppliers,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:suppliers,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:suppliers,update');
    Route::post('/', 'store')->middleware('check.permissions:suppliers,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:suppliers,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:suppliers,update');
});

Route::prefix('live-code')->controller(MenuController::class)->group(function () {
    Route::get('/', 'index');
});