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

    // household_idでスコープして1件取得（別の世帯のユーザーは取得できない）
    public function findInHousehold(int $householdId, int $userId): ?User;
}
