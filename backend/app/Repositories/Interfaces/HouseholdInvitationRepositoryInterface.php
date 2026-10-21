<?php

namespace App\Repositories\Interfaces;

use App\Models\HouseholdInvitation;

/**
 * HouseholdInvitationモデルへのデータアクセスを抽象化するインターフェース
 */
interface HouseholdInvitationRepositoryInterface
{
    public function create(array $data): HouseholdInvitation;

    public function update(HouseholdInvitation $invitation, array $data): HouseholdInvitation;

    public function deleteUnusedForHousehold(int $householdId): void;

    public function findValidByTokenHash(string $tokenHash, bool $lockForUpdate = false): ?HouseholdInvitation;
}
