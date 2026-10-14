<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Interfaces\HouseholdRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * 認証（登録・ログイン・ログアウト）に関するビジネスロジック
 */
class AuthService
{
    /**
     * 世帯名の初期値の末尾に付ける文字列（世帯名の初期値は「{ユーザー名}の家計簿」）
     */
    private const HOUSEHOLD_NAME_SUFFIX = 'の家計簿';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly HouseholdRepositoryInterface $householdRepository,
    ) {}

    /**
     * ユーザーを1人世帯とともに新規作成し、Sanctumのセッションにログインさせる
     */
    public function register(array $data): User
    {
        // 世帯とユーザーは片方だけ残らないよう、1つのトランザクションで作る
        $user = DB::transaction(function () use ($data) {
            $household = $this->householdRepository->create([
                'name' => $this->defaultHouseholdName($data['name']),
            ]);

            // パスワードはハッシュ化してから保存する
            return $this->userRepository->create([
                'household_id' => $household->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        });

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

    /**
     * ユーザー名から世帯名の初期値を作る（世帯名の列の長さ255文字に収まるよう、ユーザー名を切り詰める）
     */
    private function defaultHouseholdName(string $userName): string
    {
        return mb_substr($userName, 0, 255 - mb_strlen(self::HOUSEHOLD_NAME_SUFFIX)).self::HOUSEHOLD_NAME_SUFFIX;
    }
}
