<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 新規登録が無効のとき、リクエストを404で拒否する
 */
class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        // バリデーションより前に弾く（登録の可否や入力内容を外から探られないようにする）
        abort_unless(config('auth.registration_enabled'), 404);

        return $next($request);
    }
}
