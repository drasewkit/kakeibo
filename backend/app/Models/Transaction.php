<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 収支（1件の収入または支出）
 *
 * @property int $household_id 所有する世帯のID
 * @property int $user_id 記帳したユーザーのID
 * @property TransactionType $type casts()でenumに変換される
 * @property Carbon $date casts()でCarbonに変換される
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = ['household_id', 'user_id', 'category_id', 'type', 'amount', 'date', 'memo', 'image_path'];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'date' => 'date',
        ];
    }

    /**
     * この収支を所有する世帯
     *
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    // この収支を記帳したユーザー
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
