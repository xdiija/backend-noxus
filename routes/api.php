<?php

use App\Http\Controllers\AnimalSizeController;
use App\Http\Controllers\AnimalStatusController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreedController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
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

/*
|--------------------------------------------------------------------------
| Shelter module — animal reference data (lookups)
|--------------------------------------------------------------------------
| Roles/permissions are deferred for shelter resources for now: keep `auth`
| and add `check.permissions:<route>,<action>` later (see roadmap Module 1).
|
| species / breeds are global reference data (read-only, shared by all orgs).
| animal-sizes / animal-statuses / tags are org-customizable configurable
| lookups: list returns system defaults + the org's own rows; writes only
| ever touch the org's own rows.
*/
Route::prefix('species')->middleware('auth')->controller(SpeciesController::class)->group(function () {
    Route::get('/', 'index');
});

Route::prefix('breeds')->middleware('auth')->controller(BreedController::class)->group(function () {
    Route::get('/', 'index');
});

Route::prefix('animal-sizes')->middleware('auth')->controller(AnimalSizeController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

Route::prefix('animal-statuses')->middleware('auth')->controller(AnimalStatusController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

Route::prefix('tags')->middleware('auth')->controller(TagController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

Route::prefix('live-code')->controller(MenuController::class)->group(function () {
    Route::get('/', 'index');
});
