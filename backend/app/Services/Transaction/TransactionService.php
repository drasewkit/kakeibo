<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 収支（Transaction）に関するビジネスロジック
 */
class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * ログインユーザー自身の収支一覧を取得する（年月・種別・カテゴリで絞り込み可）。
     * あわせて対象期間の収入・支出・差引の集計値と、年セレクター用の登録済み年一覧も返す
     */
    public function getList(int $userId, array $filters): array
    {
        // レスポンスの形はResourceが決めるため、ここでは3つの値を素のまま返す
        return [
            'paginator' => $this->transactionRepository->paginateForUser($userId, $filters),
            'summary' => $this->transactionRepository->summarizeForUser($userId, $filters),
            'availableYears' => $this->transactionRepository->getAvailableYearsForUser($userId),
        ];
    }

    /**
     * ログインユーザー自身の収支を1件取得する
     */
    public function getDetail(int $userId, int $transactionId): Transaction
    {
        return $this->findOrFail($userId, $transactionId);
    }

    /**
     * 収支を新規登録する
     */
    public function create(int $userId, array $data): Transaction
    {
        return $this->transactionRepository->create($userId, $data);
    }

    /**
     * ログインユーザー自身の収支を更新する
     */
    public function update(int $userId, int $transactionId, array $data): Transaction
    {
        $transaction = $this->findOrFail($userId, $transactionId);

        return $this->transactionRepository->update($transaction, $data);
    }

    /**
     * ログインユーザー自身の収支を削除する。添付画像があればストレージからも削除する
     */
    public function delete(int $userId, int $transactionId): void
    {
        $transaction = $this->findOrFail($userId, $transactionId);

        if ($transaction->image_path) {
            Storage::disk('local')->delete($transaction->image_path);
        }

        $this->transactionRepository->delete($transaction);
    }

    /**
     * 収支に画像を添付する。既に添付済みの場合は古い画像をストレージから削除して差し替える
     */
    public function uploadImage(int $userId, int $transactionId, UploadedFile $image): Transaction
    {
        $transaction = $this->findOrFail($userId, $transactionId);

        if ($transaction->image_path) {
            Storage::disk('local')->delete($transaction->image_path);
        }

        // 他人のuser_idを推測してもアクセスできないよう、ユーザーごとのディレクトリに保存する
        $path = $image->store("transaction-images/{$userId}", 'local');

        return $this->transactionRepository->update($transaction, ['image_path' => $path]);
    }

    /**
     * 収支に添付された画像のストレージ上のパスを取得する。
     * 画像が添付されていない場合も存在しない場合と区別せず404として扱う
     */
    public function getImagePath(int $userId, int $transactionId): string
    {
        $transaction = $this->findOrFail($userId, $transactionId);

        if (! $transaction->image_path) {
            throw new ModelNotFoundException('Transaction image not found.');
        }

        return $transaction->image_path;
    }

    /**
     * 収支に添付された画像を削除する（収支自体は削除しない）
     */
    public function deleteImage(int $userId, int $transactionId): Transaction
    {
        $transaction = $this->findOrFail($userId, $transactionId);

        if ($transaction->image_path) {
            Storage::disk('local')->delete($transaction->image_path);
        }

        return $this->transactionRepository->update($transaction, ['image_path' => null]);
    }

    /**
     * user_idでスコープした上で収支を取得する。
     * 存在しない場合と他人の収支だった場合を区別せず404として扱う（所有権の有無を漏らさないため）
     */
    private function findOrFail(int $userId, int $transactionId): Transaction
    {
        $transaction = $this->transactionRepository->findForUser($userId, $transactionId);

        if (! $transaction) {
            throw new ModelNotFoundException('Transaction not found.');
        }

        return $transaction;
    }
}
