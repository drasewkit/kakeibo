<?php

namespace App\Http\Middleware;

use App\Services\Household\HouseholdService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 新規登録が無効のとき、リクエストを404で拒否する
 *
 * 有効な招待トークン付きの登録だけは、無効のときも通す
 */
class EnsureRegistrationEnabled
{
    public function __construct(
        private readonly HouseholdService $householdService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // バリデーションより前に弾く（登録の可否や入力内容を外から探られないようにする）。
        // トークンは「ある」だけでなく有効かまで確かめる。でたらめなトークンで通すと、
        // 入力チェックの「使用済みのメールアドレス」から登録済みかどうかを探られてしまう
        $token = $request->input('invitationToken');
        $hasValidInvitation = is_string($token) && $token !== '' && $this->householdService->hasValidInvitation($token);

        abort_unless(config('auth.registration_enabled') || $hasValidInvitation, 404);

        return $next($request);
    }
}
