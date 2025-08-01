<?php

use App\Http\Controllers\{
    AccountController,
    AuthController,
    CustomerController,
    SupplierController,
    MenuController,
    PaymentMethodController,
    RoleController,
    TransactionCategoryController,
    TransactionController,
    UserController,
    PaymentController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('login', 'login')->name('login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('me', 'me')->name('me');
});

/**
 * Core
 */
Route::prefix('users')->middleware('auth')->controller(UserController::class)->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/', 'index')->middleware('check.permissions:Usuários,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Usuários,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Usuários,update');
    Route::post('/', 'store')->middleware('check.permissions:Usuários,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Usuários,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Usuários,update');
});

Route::prefix('roles')->middleware('auth')->controller(RoleController::class)->group(function () {
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:Perfis,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Perfis,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Perfis,update');
    Route::post('/', 'store')->middleware('check.permissions:Perfis,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Perfis,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Perfis,update');
});

Route::prefix('menus')->middleware('auth')->controller(MenuController::class)->group(function () {
    Route::get('/getbyroles', 'getByRoles');
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:Menus,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Menus,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Menus,update');
    Route::post('/', 'store')->middleware('check.permissions:Menus,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Menus,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Menus,update');
});

Route::prefix('customers')->middleware('auth')->controller(CustomerController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:Clientes,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Clientes,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Clientes,update');
    Route::post('/', 'store')->middleware('check.permissions:Clientes,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Clientes,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Clientes,update');
});

Route::prefix('suppliers')->middleware('auth')->controller(SupplierController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:Fornecedores,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Fornecedores,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Fornecedores,update');
    Route::post('/', 'store')->middleware('check.permissions:Fornecedores,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Fornecedores,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Fornecedores,update');
});

/**
 * Financeiro
 */
Route::prefix('accounts')->middleware('auth')->controller(AccountController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:Contas,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Contas,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Contas,update');
    Route::post('/', 'store')->middleware('check.permissions:Contas,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Contas,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Contas,update');
});

Route::prefix('transaction-categories')->middleware('auth')->controller(TransactionCategoryController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show')->middleware('check.permissions:Contas,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Contas,update');
    Route::post('/', 'store')->middleware('check.permissions:Contas,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Contas,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Contas,update');
});

Route::prefix('payment-methods')->middleware('auth')->controller(PaymentMethodController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:Métodos de Pagamento,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Métodos de Pagamento,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Métodos de Pagamento,update');
    Route::post('/', 'store')->middleware('check.permissions:Métodos de Pagamento,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Métodos de Pagamento,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Métodos de Pagamento,update');
});

Route::prefix('transactions')->middleware('auth')->controller(TransactionController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:Transações,view');
    Route::get('/get-payments', 'getPayments')->middleware('check.permissions:Transações,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:Transações,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:Transações,update');
    Route::post('/', 'store')->middleware('check.permissions:Transações,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:Transações,update');
    Route::put('/payments/{id}/status', 'changePaymentStatus')->middleware('check.permissions:Transações,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:Transações,update');
});