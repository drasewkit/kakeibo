<?php

namespace App\Services\Household;

use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\User;
use App\Repositories\Interfaces\HouseholdInvitationRepositoryInterface;
use App\Repositories\Interfaces\HouseholdRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 世帯（詳細の取得・世帯名の変更・招待）に関するビジネスロジック
 *
 * 対象は常にログインユーザーが属する世帯で、他の世帯のIDは受け取らない。
 */
class HouseholdService
{
    /**
     * 招待リンクの有効期間（日）
     */
    private const INVITATION_TTL_DAYS = 7;

    public function __construct(
        private readonly HouseholdRepositoryInterface $householdRepository,
        private readonly HouseholdInvitationRepositoryInterface $invitationRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * ログインユーザーの世帯をメンバーとともに返す
     */
    public function getDetail(User $user): Household
    {
        return $this->householdRepository->loadMembers($user->household);
    }

    /**
     * ログインユーザーの世帯の名前を変更する
     */
    public function update(User $user, array $data): Household
    {
        $household = $this->householdRepository->update($user->household, ['name' => $data['name']]);

        return $this->householdRepository->loadMembers($household);
    }

    /**
     * ログインユーザーの世帯への招待を作り、招待トークンとともに返す
     *
     * @return array{invitation: HouseholdInvitation, token: string}
     */
    public function createInvitation(User $user): array
    {
        // 世帯が埋まっていれば招待できない
        $this->ensureNotFull($user->household, 'household');

        $token = Str::random(40);

        // 有効な招待は1世帯1つにするため、未使用の古い招待を消してから作る
        $invitation = DB::transaction(function () use ($user, $token) {
            $this->invitationRepository->deleteUnusedForHousehold($user->household_id);

            return $this->invitationRepository->create([
                'household_id' => $user->household_id,
                'created_by' => $user->id,
                'token_hash' => $this->hashToken($token),
                'expires_at' => now()->addDays(self::INVITATION_TTL_DAYS),
            ]);
        });

        return ['invitation' => $invitation, 'token' => $token];
    }

    /**
     * 招待トークンから有効な招待を返す（存在しない・期限切れ・使用済みは区別せず404）
     */
    public function getInvitation(string $token): HouseholdInvitation
    {
        $invitation = $this->invitationRepository->findValidByTokenHash($this->hashToken($token));

        if ($invitation === null) {
            throw new ModelNotFoundException('Invitation not found.');
        }

        return $invitation->load(['household' => fn ($query) => $query->withCount('users'), 'creator']);
    }

    /**
     * 招待を使って、ログインユーザーを招待先の世帯に参加させる
     *
     * 元の世帯とそこにある収支は移さず、そのまま残す（利用者が本人とパートナーの2人だけのため。一般公開前に見直す）
     */
    public function join(User $user, string $token): Household
    {
        $this->acceptInvitation(
            $token,
            fn (Household $household) => $this->userRepository->update($user, ['household_id' => $household->id]),
        );

        return $this->getDetail($user->refresh());
    }

    /**
     * 招待を検証してメンバーを追加し、招待を使用済みにする（参加・招待からの新規登録で共通）
     *
     * @param  Closure(Household): User  $addMember  招待先の世帯を受け取り、メンバーになったユーザーを返す
     * @param  string  $tokenField  世帯が埋まっていたときにエラーを返す項目名（リクエストのフィールド名）
     */
    public function acceptInvitation(string $token, Closure $addMember, string $tokenField = 'token'): User
    {
        return DB::transaction(function () use ($token, $addMember, $tokenField) {
            // 同じ招待が同時に使われないよう、招待の行をロックして取得する
            $invitation = $this->invitationRepository->findValidByTokenHash($this->hashToken($token), lockForUpdate: true);

            if ($invitation === null) {
                throw new ModelNotFoundException('Invitation not found.');
            }

            // 招待を作ったあとに世帯が埋まっていれば参加できない
            $household = $this->householdRepository->findForUpdate($invitation->household_id);
            $this->ensureNotFull($household, $tokenField);

            $member = $addMember($household);

            $this->invitationRepository->update($invitation, [
                'used_at' => now(),
                'used_by' => $member->id,
            ]);

            return $member;
        });
    }

    /**
     * 世帯が上限の人数に達していれば、指定した項目のバリデーションエラーにする
     */
    private function ensureNotFull(Household $household, string $field): void
    {
        if ($household->users()->count() >= Household::MAX_MEMBERS) {
            throw ValidationException::withMessages([
                $field => 'この世帯はすでに'.Household::MAX_MEMBERS.'人で利用しています。',
            ]);
        }
    }

    /**
     * 招待トークンを保存・照合用のハッシュ値にする
     */
    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
