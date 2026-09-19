<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * 収支登録リクエストのバリデーション（UpdateTransactionRequestの親クラスとしても使う）
 */
class CreateTransactionRequest extends FormRequest
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
            'type' => ['required', Rule::enum(TransactionType::class)],
            'category_id' => ['nullable', 'exists:categories,id'],
            // 円のみ扱う前提のため小数は許可しない
            'amount' => ['required', 'integer', 'min:1', 'max:99999999'],
            'date' => ['required', 'date'],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->input('category_id');

            if ($categoryId === null) {
                return;
            }

            // カテゴリの種類（収入/支出）と収支の種類が食い違っていないか検証する。
            // Categoryのtypeはenumにキャストされるため、比較相手もenumへ変換してから突き合わせる
            // （文字列のまま比較すると常に不一致になる）
            $category = Category::find($categoryId);
            $type = TransactionType::tryFrom((string) $this->input('type'));

            if ($category && $category->type !== $type) {
                $validator->errors()->add('category_id', 'カテゴリの種類が収支の種類と一致していません。');
            }
        });
    }
}
