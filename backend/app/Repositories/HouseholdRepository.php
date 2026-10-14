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
}
