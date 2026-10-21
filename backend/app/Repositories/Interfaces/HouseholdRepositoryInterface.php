<?php

namespace App\Repositories\Interfaces;

use App\Models\Household;

/**
 * Householdモデルへのデータアクセスを抽象化するインターフェース
 */
interface HouseholdRepositoryInterface
{
    public function create(array $data): Household;

    public function update(Household $household, array $data): Household;

    public function loadMembers(Household $household): Household;

    public function findForUpdate(int $householdId): Household;
}
