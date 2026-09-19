<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 収支カテゴリ（システムで固定seedし、ユーザーによる追加編集は不可）
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'type'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
