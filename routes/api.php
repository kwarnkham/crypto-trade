<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['agent'])->controller(BalanceController::class)->prefix('/balance')->group(function () {
    Route::post('deposit', 'deposit');
    Route::post('confirm-deposit', 'confirmDeposit');
    Route::post('cancel-deposit', 'cancelDeposit');
});

Route::middleware(['auth:sanctum'])->get('admin', function (Request $request) {
    return $request->user();
});


Route::controller(AuthController::class)->prefix('/admin')->group(function () {
    Route::post('login', 'login');
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('change-password', 'changePassword');
    });
});

Route::controller(WalletController::class)->middleware(['auth:sanctum'])->prefix('/wallets')->group(function () {
    Route::post('', 'store');
    Route::get('', 'index');
    Route::post('{wallet}/activate', 'activate');
});
