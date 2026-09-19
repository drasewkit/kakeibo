<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 固定の収支カテゴリをseedする（ユーザーが追加・編集することはない）
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $expenseCategories = ['食費', '日用品', '交通費', '住居費', '水道光熱費', '通信費', '医療費', '娯楽費', 'その他'];
        $incomeCategories = ['給与', '副収入', 'その他'];

        // 支出カテゴリを登録
        foreach ($expenseCategories as $name) {
            Category::create(['name' => $name, 'type' => TransactionType::Expense]);
        }

        // 収入カテゴリを登録（「その他」は支出側とtypeで区別されるため重複してよい）
        foreach ($incomeCategories as $name) {
            Category::create(['name' => $name, 'type' => TransactionType::Income]);
        }
    }
}
