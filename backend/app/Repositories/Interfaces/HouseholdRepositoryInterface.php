<?php

namespace App\Repositories\Interfaces;

use App\Models\Household;

/**
 * Householdモデルへのデータアクセスを抽象化するインターフェース
 */
interface HouseholdRepositoryInterface
{
    public function create(array $data): Household;
}
