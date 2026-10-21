<?php

namespace App\Repositories;

use App\Models\Household;
use App\Repositories\Interfaces\HouseholdRepositoryInterface;

/**
 * Householdモデルへのデータアクセスを担うRepository
 */
class HouseholdRepository implements HouseholdRepositoryInterface
{
    public function create(array $data): Household
    {
        return Household::create($data);
    }

    public function update(Household $household, array $data): Household
    {
        $household->update($data);

        return $household;
    }

    public function findForUpdate(int $householdId): Household
    {
        // 同時に参加しても人数の上限を超えないよう、世帯の行をロックする
        return Household::query()->lockForUpdate()->findOrFail($householdId);
    }

    public function countMembers(Household $household): int
    {
        return $household->users()->count();
    }

    public function loadMembers(Household $household): Household
    {
        // メンバーは参加した順（id順）に並べる
        return $household->load(['users' => fn ($query) => $query->orderBy('id')]);
    }
}
