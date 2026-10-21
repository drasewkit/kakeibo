<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * Userモデルへのデータアクセスを担うRepository
 */
class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function findInHousehold(int $householdId, int $userId): ?User
    {
        // 同じ世帯のメンバーだけを取得できるようにする
        return User::query()
            ->where('household_id', $householdId)
            ->find($userId);
    }
}
