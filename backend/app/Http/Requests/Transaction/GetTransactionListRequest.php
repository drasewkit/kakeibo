<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 収支一覧取得リクエストのバリデーション（絞り込み条件はすべて任意）
 */
class GetTransactionListRequest extends FormRequest
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
            'year' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'categoryId' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Repositoryが期待するキー（DBのカラム名に寄せたsnake_case）に変換する
     *
     * @return array<string, mixed>
     */
    public function toFilters(): array
    {
        return [
            'year' => $this->validated('year'),
            'month' => $this->validated('month'),
            'type' => $this->validated('type'),
            'category_id' => $this->validated('categoryId'),
            'page' => $this->validated('page'),
        ];
    }
}
