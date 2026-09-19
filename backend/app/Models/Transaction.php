<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 収支（1件の収入または支出）
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'category_id', 'type', 'amount', 'date', 'memo', 'image_path'];

    // image_pathはストレージ内部のパスでありAPIレスポンスには含めない（has_imageのみ公開する）
    protected $hidden = ['image_path'];

    protected $appends = ['has_image'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date' => 'date',
        ];
    }

    // 画像が添付されているかどうか（実際のパスは公開せず、取得は専用エンドポイント経由にする）
    protected function hasImage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path !== null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
