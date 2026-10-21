<?php

namespace App\Http\Resources\Household;

use App\Models\Household;
use App\Models\HouseholdInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 招待内容のAPIレスポンス（招待を受けた人が参加前に確認する情報だけを返す）
 *
 * @mixin HouseholdInvitation
 */
class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'householdId' => $this->household_id,
            'householdName' => $this->household->name,
            'invitedBy' => ['name' => $this->creator->name],
            'expiresAt' => $this->expires_at->toJSON(),
            // 世帯が埋まっていれば参加できない（画面で参加ボタンを出さないために使う）
            'isFull' => $this->household->users_count >= Household::MAX_MEMBERS,
        ];
    }
}
