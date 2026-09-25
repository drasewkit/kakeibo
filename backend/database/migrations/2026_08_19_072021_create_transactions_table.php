<?php

use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // comment()は制約(constrained)より前に呼ぶ必要がある
            $table->foreignId('user_id')->comment('登録したユーザーのID')->constrained()->cascadeOnDelete();
            // カテゴリが削除されてもtransactionsは残す（種別・金額の記録を消さないため）
            $table->foreignId('category_id')->nullable()->comment('カテゴリID（カテゴリ削除時はNULL）')->constrained()->nullOnDelete();
            // categoriesのtypeと重複するが、category_idがnullになっても収支種別が失われないよう
            // あえて非正規化して持たせている
            $table->enum('type', TransactionType::values())->comment('種別（income: 収入 / expense: 支出）');
            // 円のみ扱う前提のため小数は持たず整数で保存する（符号はtypeで表すため非負）
            $table->unsignedInteger('amount')->comment('金額（円単位の整数）');
            $table->date('date')->comment('取引日');
            $table->text('memo')->nullable()->comment('メモ');
            $table->timestamps();

            // 一覧表示・月次フィルタ用のインデックス
            $table->index(['user_id', 'date']);
            // 種別フィルタ用のインデックス
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
