<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::macro('tron', function () {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'TRON-PRO-API-KEY' => config('app')['tron_api_key'],
            ])->baseUrl(config('app')['tron_api_url']);
        });

        Http::macro('tron2', function () {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'TRON-PRO-API-KEY' => config('app')['tron_api_key2'],
            ])->baseUrl(config('app')['tron_api_url']);
        });

        Http::macro('tron3', function () {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'TRON-PRO-API-KEY' => config('app')['tron_api_key3'],
            ])->baseUrl(config('app')['tron_api_url']);
        });
    }
}
