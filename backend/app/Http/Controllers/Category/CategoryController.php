<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Http\Resources\Category\CategoryResource;
use App\Services\Category\CategoryService;
use Illuminate\Http\JsonResponse;

/**
 * カテゴリ（システムで固定seedしたマスタの参照のみ）
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function getList(): JsonResponse
    {
        // 収入・支出用に固定でseedしたカテゴリ一覧を返す
        return CategoryResource::collection($this->categoryService->getList())->response();
    }
}
