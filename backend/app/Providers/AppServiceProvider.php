<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
        // Resourceの既定の data ラップを無効化する。
        // レスポンスの形はResource側で明示する方針のため、暗黙の入れ子を作らない
        JsonResource::withoutWrapping();

        //
    }
}
