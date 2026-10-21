<?php

namespace App\Repositories;

use App\Models\HouseholdInvitation;
use App\Repositories\Interfaces\HouseholdInvitationRepositoryInterface;

/**
 * HouseholdInvitationモデルへのデータアクセスを担うRepository
 */
class HouseholdInvitationRepository implements HouseholdInvitationRepositoryInterface
{
    public function create(array $data): HouseholdInvitation
    {
        return HouseholdInvitation::create($data);
    }

    public function update(HouseholdInvitation $invitation, array $data): HouseholdInvitation
    {
        $invitation->update($data);

        return $invitation;
    }

    public function deleteUnusedForHousehold(int $householdId): void
    {
        // 使用済みの招待は誰がいつ参加したかの記録として残す
        HouseholdInvitation::query()
            ->where('household_id', $householdId)
            ->whereNull('used_at')
            ->delete();
    }

    public function findValidByTokenHash(string $tokenHash, bool $lockForUpdate = false): ?HouseholdInvitation
    {
        // 未使用かつ期限内の招待だけを対象にする（存在しない・期限切れ・使用済みは区別しない）
        return HouseholdInvitation::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->first();
    }
}
