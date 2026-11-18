<?php

namespace App\Http\Resources\Household;

use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 世帯のAPIレスポンス
 *
 * @mixin Household
 */
class HouseholdResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'members' => HouseholdMemberResource::collection($this->users),
        ];
    }
}
