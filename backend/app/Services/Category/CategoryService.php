<?php

namespace App\Services\Category;

use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * カテゴリに関するビジネスロジック
 */
class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * 種類（収入/支出）・表示順で並んだカテゴリ一覧を取得する
     */
    public function getList(): Collection
    {
        return $this->categoryRepository->getOrderedList();
    }
}
