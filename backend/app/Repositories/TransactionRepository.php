<?php

namespace App\Repositories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Transactionモデルへのデータアクセスを担うRepository
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    public function paginateForHousehold(int $householdId, array $filters, int $perPage = 500): LengthAwarePaginator
    {
        // 常にhousehold_idでスコープし、別の世帯の収支が混ざらないようにする
        $query = Transaction::query()
            ->where('household_id', $householdId)
            ->with('category');

        $this->applyDateRangeFilter($query, $filters);

        // 収支種別（income/expense）で絞り込み
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // カテゴリで絞り込み
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->orderByDesc('date')->orderByDesc('id')->paginate($perPage);
    }

    public function summarizeForHousehold(int $householdId, array $filters): array
    {
        // 一覧の絞り込み（種別・カテゴリ）には関係なく、対象期間全体の収入・支出を集計する
        $query = Transaction::query()->where('household_id', $householdId);

        $this->applyDateRangeFilter($query, $filters);

        $totalsByType = $query
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $income = (int) ($totalsByType[TransactionType::Income->value] ?? 0);
        $expense = (int) ($totalsByType[TransactionType::Expense->value] ?? 0);

        // ここのキーはAPIレスポンスの項目名であり、種別の値とは別物なのでリテラルのままにする
        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getAvailableYearsForHousehold(int $householdId): array
    {
        // 年セレクターの選択肢用に、収支が存在する年だけを重複なく降順で返す。
        // 戻り値の型はドライバによって文字列にも数値にもなるのでintへ寄せる
        return Transaction::query()
            ->where('household_id', $householdId)
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM transactions.date) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();
    }

    // 年月指定がある場合は日付の範囲検索にする
    // （whereYear/whereMonthは列に関数がかかりインデックスが効かなくなるため使わない）
    private function applyDateRangeFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['year']) && ! empty($filters['month'])) {
            $start = sprintf('%04d-%02d-01', $filters['year'], $filters['month']);
            $end = date('Y-m-t', strtotime($start));
            $query->whereBetween('date', [$start, $end]);
        }
    }

    public function findForHousehold(int $householdId, int $transactionId): ?Transaction
    {
        // household_idでスコープすることで、別の世帯の収支IDを指定されても取得できないようにする
        return Transaction::query()
            ->where('household_id', $householdId)
            ->with('category')
            ->find($transactionId);
    }

    public function create(int $householdId, int $userId, array $data): Transaction
    {
        $transaction = Transaction::create([...$data, 'household_id' => $householdId, 'user_id' => $userId]);

        return $transaction->load('category');
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);

        return $transaction->load('category');
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
