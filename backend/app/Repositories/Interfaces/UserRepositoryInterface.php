<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

/**
 * Userモデルへのデータアクセスを抽象化するインターフェース
 */
interface UserRepositoryInterface
{
    public function create(array $data): User;
}
