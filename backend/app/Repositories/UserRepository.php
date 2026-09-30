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

    public function findVisibleTo(int $viewerId, int $userId): ?User
    {
        // 閲覧できるのは現状では自分だけ。世帯を導入したら同じ世帯のメンバーまで広げる
        return User::query()
            ->where('id', $viewerId)
            ->find($userId);
    }
}
