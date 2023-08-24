<?php

use App\Http\Controllers\BalanceController;
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
});
