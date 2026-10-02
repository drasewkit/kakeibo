<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Transactionモデルへのデータアクセスを抽象化するインターフェース
 */
interface TransactionRepositoryInterface
{
    // household_idでスコープした一覧をページネーション付きで取得
    public function paginateForHousehold(int $householdId, array $filters, int $perPage = 500): LengthAwarePaginator;

    // household_idでスコープし、対象期間の収入・支出・差引を集計
    public function summarizeForHousehold(int $householdId, array $filters): array;

    // household_idでスコープし、収支が1件でも存在する年の一覧を降順で取得（年セレクターの選択肢用）
    public function getAvailableYearsForHousehold(int $householdId): array;

    // household_idでスコープして1件取得（別の世帯のレコードは取得できない）
    public function findForHousehold(int $householdId, int $transactionId): ?Transaction;

    // 世帯の収支として、記帳したユーザーとともに登録する
    public function create(int $householdId, int $userId, array $data): Transaction;

    public function update(Transaction $transaction, array $data): Transaction;

    public function delete(Transaction $transaction): void;
}
