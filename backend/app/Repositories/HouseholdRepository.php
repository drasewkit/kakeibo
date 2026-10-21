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

    public function loadMembers(Household $household): Household
    {
        // メンバーは参加した順（id順）に並べる
        return $household->load(['users' => fn ($query) => $query->orderBy('id')]);
    }
}
