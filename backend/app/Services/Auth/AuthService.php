<?php

namespace App\Services\Auth;

use App\Models\Household;
use App\Models\User;
use App\Repositories\Interfaces\HouseholdRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Household\HouseholdService;
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
        private readonly HouseholdService $householdService,
    ) {}

    /**
     * ユーザーを新規作成し、Sanctumのセッションにログインさせる
     *
     * 招待トークンがあれば招待先の世帯に入れ、無ければ1人世帯を作る。
     */
    public function register(array $data): User
    {
        $user = empty($data['invitationToken'])
            ? $this->registerWithNewHousehold($data)
            : $this->registerWithInvitation($data);

        Auth::login($user);

        return $user;
    }

    /**
     * 1人世帯とともにユーザーを作る
     */
    private function registerWithNewHousehold(array $data): User
    {
        // 世帯とユーザーは片方だけ残らないよう、1つのトランザクションで作る
        return DB::transaction(function () use ($data) {
            $household = $this->householdRepository->create([
                'name' => $this->defaultHouseholdName($data['name']),
            ]);

            return $this->createUser($household, $data);
        });
    }

    /**
     * 招待を検証し、招待先の世帯のメンバーとしてユーザーを作る（無効な招待は404、世帯が埋まっていれば422）
     */
    private function registerWithInvitation(array $data): User
    {
        return $this->householdService->acceptInvitation(
            $data['invitationToken'],
            fn (Household $household) => $this->createUser($household, $data),
            'invitationToken',
        );
    }

    /**
     * 指定した世帯のメンバーとしてユーザーを作る
     */
    private function createUser(Household $household, array $data): User
    {
        // パスワードはハッシュ化してから保存する
        return $this->userRepository->create([
            'household_id' => $household->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
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
