<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\Category\CategoryResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 収支のAPIレスポンス
 *
 * image_pathは公開せず、添付の有無だけをhasImageで返す。
 * 画像本体は専用エンドポイントから取得する。
 *
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'categoryId' => $this->category_id,
            // カテゴリは常にeager loadされるが、削除済みの場合はnullになりうる
            'category' => $this->relationLoaded('category') && $this->category !== null
                ? CategoryResource::make($this->category)
                : null,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'date' => $this->date->toDateString(),
            'memo' => $this->memo,
            'hasImage' => $this->image_path !== null,
            'createdAt' => $this->created_at?->toJSON(),
            'updatedAt' => $this->updated_at?->toJSON(),
        ];
    }
}
