<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 認証（登録・ログイン・ログアウト・ログイン中ユーザーの取得）
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        // ユーザーを作成し、そのままログイン状態にする
        $user = $this->authService->register($request->validated());

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // メール・パスワードを検証してログイン
        $user = $this->authService->attempt($request->validated());

        // セッションIDを再発行し、セッション固定攻撃を防ぐ
        $request->session()->regenerate();

        return UserResource::make($user)->response();
    }

    public function logout(Request $request): Response
    {
        // 認証ガードからログアウト
        $this->authService->logout();

        // セッションを破棄し、CSRFトークンも再発行する
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function getUser(Request $request): JsonResponse
    {
        // Sanctumのセッション認証で解決されたログインユーザーを返す
        return UserResource::make($request->user())->response();
    }
}
