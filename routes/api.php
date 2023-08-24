<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
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


Route::middleware(['agent'])->controller(DepositController::class)->prefix('/deposits')->group(function () {
    Route::post('', 'store');
    Route::post('{depoist}/confirm', 'confirm');
    Route::post('{deposit}/cancel', 'cancel');
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
