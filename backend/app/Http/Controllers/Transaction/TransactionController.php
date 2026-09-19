<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\CreateTransactionRequest;
use App\Http\Requests\Transaction\DeleteTransactionImageRequest;
use App\Http\Requests\Transaction\DeleteTransactionRequest;
use App\Http\Requests\Transaction\GetTransactionDetailRequest;
use App\Http\Requests\Transaction\GetTransactionImageRequest;
use App\Http\Requests\Transaction\GetTransactionListRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Requests\Transaction\UploadTransactionImageRequest;
use App\Http\Resources\Transaction\TransactionListResource;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 収支（一覧・詳細・登録・更新・削除と、添付画像の取得・登録・削除）
 *
 * 所有権のチェックはService/Repositoryがuser_idでスコープして行う。
 * 他人のIDを指定した場合は存在しない場合と区別せず404になる。
 */
class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function getList(GetTransactionListRequest $request): JsonResponse
    {
        // 年月・種別・カテゴリで絞り込んでページネーション取得する
        $result = $this->transactionService->getList($request->user()->id, $request->toFilters());

        return TransactionListResource::make(
            $result['paginator'],
            $result['summary'],
            $result['availableYears'],
        )->response();
    }

    public function getDetail(GetTransactionDetailRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->getDetail(
            $request->user()->id,
            $request->validated('transactionId'),
        );

        return TransactionResource::make($transaction)->response();
    }

    public function create(CreateTransactionRequest $request): JsonResponse
    {
        // ログインユーザー自身の収支として登録する
        $transaction = $this->transactionService->create($request->user()->id, $request->toAttributes());

        return TransactionResource::make($transaction)->response()->setStatusCode(201);
    }

    public function update(UpdateTransactionRequest $request): JsonResponse
    {
        // toAttributes()は更新対象のIDを含まないため、そのまま更新内容として渡せる
        $transaction = $this->transactionService->update(
            $request->user()->id,
            $request->validated('transactionId'),
            $request->toAttributes(),
        );

        return TransactionResource::make($transaction)->response();
    }

    public function delete(DeleteTransactionRequest $request): Response
    {
        $this->transactionService->delete($request->user()->id, $request->validated('transactionId'));

        return response()->noContent();
    }

    public function getImage(GetTransactionImageRequest $request): StreamedResponse
    {
        // 画像が添付されていない場合も、存在しない場合と区別せず404になる
        $path = $this->transactionService->getImagePath(
            $request->user()->id,
            $request->validated('transactionId'),
        );

        return Storage::disk('local')->response($path);
    }

    public function uploadImage(UploadTransactionImageRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->uploadImage(
            $request->user()->id,
            $request->validated('transactionId'),
            $request->file('image'),
        );

        return TransactionResource::make($transaction)->response();
    }

    public function deleteImage(DeleteTransactionImageRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->deleteImage(
            $request->user()->id,
            $request->validated('transactionId'),
        );

        return TransactionResource::make($transaction)->response();
    }
}
