<?php

namespace App\Services\Household;

use App\Models\Household;
use App\Models\User;
use App\Repositories\Interfaces\HouseholdRepositoryInterface;

/**
 * 世帯（詳細の取得・世帯名の変更）に関するビジネスロジック
 *
 * 対象は常にログインユーザーが属する世帯で、他の世帯のIDは受け取らない。
 */
class HouseholdService
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $householdRepository,
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
}
