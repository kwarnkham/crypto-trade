<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $records = (array)json_decode((string)Cache::get('api-records'));
    arsort($records);
    return ['Laravel' => app()->version(), 'App' => config('app.name'), 'Api Records' => $records];
});

require __DIR__ . '/auth.php';
