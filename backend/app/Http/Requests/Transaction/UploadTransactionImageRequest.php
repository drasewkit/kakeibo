<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 収支への画像添付リクエストのバリデーション
 */
class UploadTransactionImageRequest extends FormRequest
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
            // レシート等の写真を想定し、5MBまで・画像形式のみ許可する
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,heic,webp', 'max:5120'],
        ];
    }
}
