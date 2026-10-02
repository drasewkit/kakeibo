<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 収支（Transaction）に関するビジネスロジック
 *
 * 収支は世帯が所有する。ログインユーザーが扱えるのは、自分の世帯の収支（世帯の他のメンバーが記帳したものを含む）。
 */
class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * ログインユーザーの世帯の収支一覧を取得する（年月・種別・カテゴリで絞り込み可）。
     * あわせて対象期間の収入・支出・差引の集計値と、年セレクター用の登録済み年一覧も返す
     */
    public function getList(User $user, array $filters): array
    {
        // レスポンスの形はResourceが決めるため、ここでは3つの値を素のまま返す
        return [
            'paginator' => $this->transactionRepository->paginateForHousehold($user->household_id, $filters),
            'summary' => $this->transactionRepository->summarizeForHousehold($user->household_id, $filters),
            'availableYears' => $this->transactionRepository->getAvailableYearsForHousehold($user->household_id),
        ];
    }

    /**
     * ログインユーザーの世帯の収支を1件取得する
     */
    public function getDetail(User $user, int $transactionId): Transaction
    {
        return $this->findOrFail($user, $transactionId);
    }

    /**
     * ログインユーザーの世帯の収支として、ログインユーザーを記帳者にして新規登録する
     */
    public function create(User $user, array $data): Transaction
    {
        return $this->transactionRepository->create($user->household_id, $user->id, $data);
    }

    /**
     * ログインユーザーの世帯の収支を更新する（記帳者は変えない）
     */
    public function update(User $user, int $transactionId, array $data): Transaction
    {
        $transaction = $this->findOrFail($user, $transactionId);

        return $this->transactionRepository->update($transaction, $data);
    }

    /**
     * ログインユーザーの世帯の収支を削除する。添付画像があればストレージからも削除する
     */
    public function delete(User $user, int $transactionId): void
    {
        $transaction = $this->findOrFail($user, $transactionId);

        if ($transaction->image_path) {
            Storage::disk('local')->delete($transaction->image_path);
        }

        $this->transactionRepository->delete($transaction);
    }

    /**
     * 収支に画像を添付する。既に添付済みの場合は古い画像をストレージから削除して差し替える
     */
    public function uploadImage(User $user, int $transactionId, UploadedFile $image): Transaction
    {
        $transaction = $this->findOrFail($user, $transactionId);

        if ($transaction->image_path) {
            Storage::disk('local')->delete($transaction->image_path);
        }

        // 世帯ごとのディレクトリに保存する（取得時の認可はパスではなく収支の所有で判定する）
        $path = $image->store("transaction-images/{$transaction->household_id}", 'local');

        return $this->transactionRepository->update($transaction, ['image_path' => $path]);
    }

    /**
     * 収支に添付された画像のストレージ上のパスを取得する。
     * 画像が添付されていない場合も存在しない場合と区別せず404として扱う
     */
    public function getImagePath(User $user, int $transactionId): string
    {
        $transaction = $this->findOrFail($user, $transactionId);

        if (! $transaction->image_path) {
            throw new ModelNotFoundException('Transaction image not found.');
        }

        return $transaction->image_path;
    }

    /**
     * 収支に添付された画像を削除する（収支自体は削除しない）
     */
    public function deleteImage(User $user, int $transactionId): Transaction
    {
        $transaction = $this->findOrFail($user, $transactionId);

        if ($transaction->image_path) {
            Storage::disk('local')->delete($transaction->image_path);
        }

        return $this->transactionRepository->update($transaction, ['image_path' => null]);
    }

    /**
     * ログインユーザーの世帯でスコープした上で収支を取得する。
     * 存在しない場合と別の世帯の収支だった場合を区別せず404として扱う（所有権の有無を漏らさないため）
     */
    private function findOrFail(User $user, int $transactionId): Transaction
    {
        $transaction = $this->transactionRepository->findForHousehold($user->household_id, $transactionId);

        if (! $transaction) {
            throw new ModelNotFoundException('Transaction not found.');
        }

        return $transaction;
    }
}
