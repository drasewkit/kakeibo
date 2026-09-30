<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

/**
 * Userモデルへのデータアクセスを抽象化するインターフェース
 */
interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function findVisibleTo(int $viewerId, int $userId): ?User;
}
