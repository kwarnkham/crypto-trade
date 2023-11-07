<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ExtractController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
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
        Route::get('{deposit}', 'find');
    });
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('', 'index');
        Route::get('{deposit}', 'find');
    });
});

Route::controller(WithdrawController::class)->prefix('/withdraws')->group(function () {
    Route::middleware(['agent'])->prefix('/agent')->group(function () {
        Route::post('', 'store');
        Route::get('', 'index');
        Route::post('{withdraw}/confirm', 'confirm');
        Route::post('{withdraw}/cancel', 'cancel');
        Route::get('{withdraw}', 'find');
    });
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('', 'index');
    });
});

Route::controller(ExtractController::class)->prefix('/extracts')->group(function () {
    Route::middleware(['agent'])->prefix('/agent')->group(function () {
        Route::post('', 'store');
        Route::get('{extract}', 'find');
    });
});

Route::controller(TransactionController::class)->prefix('/transactions')->group(function () {
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('', 'index');
    });
});

Route::controller(TransferController::class)->prefix('/transfers')->group(function () {
    Route::middleware(['agent'])->prefix('/agent')->group(function () {
        Route::post('', 'store');
        Route::get('', 'index');
    });
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('', 'index');
    });
});


Route::controller(AuthController::class)->prefix('/admin')->group(function () {
    Route::post('login', 'login');
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('change-password', 'changePassword');
        Route::post('logout', 'logout');
    });
});

Route::controller(WalletController::class)->prefix('/wallets')->group(function () {
    Route::middleware(['agent'])->prefix('agent')->group(function () {
        Route::get('', 'index');
    });

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('', 'store');
        Route::get('', 'index');
        Route::post('{wallet}/activate', 'activate');
        Route::get('{wallet}', 'find');
        Route::post('{wallet}/stake', 'stake');
        Route::post('{wallet}/unstake', 'unstake');
        Route::post('{wallet}/withdraw-unstake', 'withdrawUnstake');
        Route::post('{wallet}/cancel-unstake', 'cancelUnstake');
    });
});



Route::controller(AgentController::class)->middleware(['auth:sanctum'])->prefix('/agents')->group(function () {
    Route::post('', 'store');
    Route::post('{agent}/toggle-status', 'toggleStatus');
    Route::post('{agent}/reset-key', 'resetKey');
    Route::put('{agent}', 'update');
    Route::get('', 'index');
});

Route::controller(AgentController::class)
    ->middleware(['agent'])
    ->prefix('/agents')->group(function () {
        Route::post('callback', 'setCallback');
    });

Route::controller(UserController::class)->prefix('/users')->group(function () {
    Route::middleware(['agent'])->prefix('/agent')->group(function () {
        Route::get('{user}', 'find');
        Route::get('', 'index');
    });
});
