<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 収支更新リクエストのバリデーション（登録時の項目に更新対象のIDを加えたもの）
 */
class UpdateTransactionRequest extends CreateTransactionRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transactionId' => ['required', 'integer'],
            ...parent::rules(),
        ];
    }
}
