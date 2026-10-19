<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        // ログインの総当たり対策。同じメール×IPは1分5回、メールを変えて試す場合に備えIP単位でも1分20回まで
        RateLimiter::for('login', function (Request $request) {
            // 本番はCloudflare Tunnel経由でしか到達できず、CF-Connecting-IPは常にCloudflareが付け直す。
            // nginx越しの$request->ip()はコンテナ内のアドレスになるため、こちらを優先する
            $ip = $request->header('CF-Connecting-IP', $request->ip());
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email.'|'.$ip),
                Limit::perMinute(20)->by($ip),
            ];
        });
    }
}
