<?php

namespace App\Http\Resources\Transaction;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 収支一覧のAPIレスポンス（ページネーション + 期間の集計 + 登録済み年の一覧）
 *
 * Laravelのページネータが吐くlinksやpath等はフロントで使わないため露出させず、
 * 必要な項目だけを明示して返す。
 */
class TransactionListResource extends JsonResource
{
    /**
     * @param  LengthAwarePaginator<int, Transaction>  $paginator
     * @param  array{income: int, expense: int, balance: int}  $summary
     * @param  array<int, int>  $availableYears
     */
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        private readonly array $summary,
        private readonly array $availableYears,
    ) {
        parent::__construct($paginator);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => TransactionResource::collection($this->paginator->items()),
            'currentPage' => $this->paginator->currentPage(),
            'lastPage' => $this->paginator->lastPage(),
            'total' => $this->paginator->total(),
            'summary' => $this->summary,
            'availableYears' => $this->availableYears,
        ];
    }
}
