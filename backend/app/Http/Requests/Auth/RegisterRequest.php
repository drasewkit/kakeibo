<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * ユーザー新規登録リクエストのバリデーション
 */
class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            // メールアドレスの重複登録を防ぐ
            'email' => ['required', 'email', 'unique:users,email'],
            // passwordConfirmationとの一致・最低文字数を検証
            'password' => ['required', 'confirmed:passwordConfirmation', Password::min(8)],
        ];
    }
}
