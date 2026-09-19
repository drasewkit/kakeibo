<?php

namespace App\Enums;

/**
 * 収支の種別。income（収入）とexpense（支出）の2種類のみを扱う。
 *
 * この値はDBのenum列・バリデーション・集計・フロントの型定義に現れるため、
 * ここをバックエンドにおける唯一の定義元とし、文字列リテラルを各所に散らさない。
 */
enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    /**
     * DBのenum列やバリデーションに渡すための値の一覧
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
