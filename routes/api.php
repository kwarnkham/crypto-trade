<?php

use App\Http\Controllers\AgentController;
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


Route::controller(DepositController::class)->prefix('/deposits')->group(function () {
    Route::middleware(['agent'])->prefix('/agent')->group(function () {
        Route::post('{deposit}/confirm', 'confirm');
        Route::post('{deposit}/cancel', 'cancel');
        Route::post('', 'store');
        Route::get('', 'index');
    });
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('', 'index');
    });
});

Route::controller(WithdrawController::class)->prefix('/withdraws')->group(function () {
    Route::middleware(['agent'])->prefix('/agent')->group(function () {
        Route::post('', 'store');
        Route::get('', 'index');
        Route::post('{withdraw}/confirm', 'confirm');
        Route::post('{withdraw}/cancel', 'cancel');
    });
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('', 'index');
    });
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
    Route::post('{wallet}/unstake', 'unstake');
    Route::post('{wallet}/withdraw-unstake', 'withdrawUnstake');
    Route::post('{wallet}/cancel-unstake', 'cancelUnstake');
});


Route::controller(AgentController::class)->middleware(['auth:sanctum'])->prefix('/agents')->group(function () {
    Route::post('', 'store');
    Route::post('{agent}/toggle-status', 'toggleStatus');
    Route::post('{agent}/reset-key', 'resetKey');
    Route::put('{agent}', 'update');
    Route::get('', 'index');
});
