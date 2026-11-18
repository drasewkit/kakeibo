<?php

namespace App\Models;

use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 世帯（家計簿を共有する単位）
 *
 * 収支は世帯が所有し、世帯のメンバー全員が閲覧・編集できる。ユーザーは必ず1つの世帯に属する。
 * 世帯は本人とパートナーの2人までとする。
 */
#[Fillable(['name'])]
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory;

    /**
     * 世帯に入れる人数の上限（本人とパートナー）
     */
    public const MAX_MEMBERS = 2;

    /**
     * この世帯に属するユーザー
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * この世帯が所有する収支
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * この世帯への招待
     *
     * @return HasMany<HouseholdInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(HouseholdInvitation::class);
    }
}
