<?php

namespace App\Http\Resources\Household;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 世帯のメンバーのAPIレスポンス（メールアドレスなどは含めない）
 *
 * @mixin User
 */
class HouseholdMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hasAvatar' => $this->avatar_path !== null,
            // 画像を差し替えるたびに変わる値。画像URLに付けてブラウザのキャッシュを無効にする
            'avatarVersion' => $this->avatar_path !== null ? substr(md5($this->avatar_path), 0, 8) : null,
        ];
    }
}
