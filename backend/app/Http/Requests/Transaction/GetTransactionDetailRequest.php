<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 収支詳細取得リクエストのバリデーション
 * （GET/POSTのみの方針のため、対象IDはルートパラメータではなくここで受け取る）
 */
class GetTransactionDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transactionId' => ['required', 'integer'],
        ];
    }
}
