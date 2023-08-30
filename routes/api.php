<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawController;
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


Route::middleware(['agent'])->controller(DepositController::class)->prefix('/deposits')->group(function () {
    Route::post('', 'store');
    Route::post('{deposit}/confirm', 'confirm');
    Route::post('{deposit}/cancel', 'cancel');
});

Route::middleware(['agent'])->controller(WithdrawController::class)->prefix('/withdraws')->group(function () {
    Route::post('', 'store');
    Route::post('{withdraw}/confirm', 'confirm');
});

Route::middleware(['agent'])->controller(TransferController::class)->prefix('/transfers')->group(function () {
    Route::post('', 'store');
});


Route::controller(AuthController::class)->prefix('/admin')->group(function () {
    Route::post('login', 'login');
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('change-password', 'changePassword');
        Route::post('logout', 'logout');
    });
});

Route::controller(WalletController::class)->middleware(['auth:sanctum'])->prefix('/wallets')->group(function () {
    Route::post('', 'store');
    Route::get('', 'index');
    Route::post('{wallet}/activate', 'activate');
    Route::get('{wallet}', 'find');
    Route::post('{wallet}/stake', 'stake');
});
