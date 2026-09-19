<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Categoryモデルへのデータアクセスを担うRepository
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    public function getOrderedList(): Collection
    {
        // 種類（income/expense）ごとにまとまるよう並び替えて取得する。
        //
        // typeでそのまま並べるとengine依存になる。MySQLはenumの定義順（income=1, expense=2）、
        // PostgreSQLはvarchar+CHECK制約になるため文字列比較（expense→income）で逆になる。
        // 収入→支出の順を意図した並びとして明示するため、標準SQLのCASEで順序を固定する。
        return Category::orderByRaw("CASE type WHEN 'income' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }
}
