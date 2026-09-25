<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

/**
 * Categoryモデルへのデータアクセスを抽象化するインターフェース
 */
interface CategoryRepositoryInterface
{
    public function getOrderedList(): Collection;
}
