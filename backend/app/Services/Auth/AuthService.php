<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * 認証（登録・ログイン・ログアウト）に関するビジネスロジック
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * ユーザーを新規作成し、Sanctumのセッションにログインさせる
     */
    public function register(array $data): User
    {
        // パスワードはハッシュ化してから保存する
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return $user;
    }

    /**
     * メールアドレス・パスワードでログインを試みる
     */
    public function attempt(array $credentials): User
    {
        // 認証失敗時は422で返るバリデーションエラーとして扱う
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        return Auth::user();
    }

    /**
     * webガードからログアウトする（セッション破棄はコントローラー側で行う）
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();
    }
}
