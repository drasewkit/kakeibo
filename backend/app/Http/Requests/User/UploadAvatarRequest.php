<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * プロフィール画像登録リクエストのバリデーション
 */
class UploadAvatarRequest extends FormRequest
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
            // 収支の添付画像と同じく、5MBまで・画像形式のみ許可する
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,heic,webp', 'max:5120'],
        ];
    }
}
