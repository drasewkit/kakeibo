<?php

namespace App\Http\Resources\Household;

use App\Models\HouseholdInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 作成した招待リンクのAPIレスポンス（トークンを含むURLはこのときだけ返す）
 *
 * @mixin HouseholdInvitation
 */
class InvitationLinkResource extends JsonResource
{
    public function __construct(HouseholdInvitation $invitation, private readonly string $token)
    {
        parent::__construct($invitation);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'url' => rtrim(config('app.frontend_url'), '/').'/invite/'.$this->token,
            'expiresAt' => $this->expires_at->toJSON(),
        ];
    }
}
