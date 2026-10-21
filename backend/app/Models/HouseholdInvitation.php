<?php

namespace App\Models;

use Database\Factories\HouseholdInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 世帯への招待（招待リンク1本に対応する）
 *
 * 有効な招待は1世帯に1つ。作り直すと未使用の古い招待は削除される。
 *
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
#[Fillable(['household_id', 'created_by', 'token_hash', 'expires_at', 'used_at', 'used_by'])]
class HouseholdInvitation extends Model
{
    /** @use HasFactory<HouseholdInvitationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * 招待先の世帯
     *
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * 招待したユーザー
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
