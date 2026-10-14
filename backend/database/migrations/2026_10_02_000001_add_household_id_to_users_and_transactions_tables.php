<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ユーザーと収支を世帯にひも付ける。
 * 既存のユーザーにはそれぞれ1人世帯を作り、そのユーザーの収支をその世帯の所有にする。
 */
return new class extends Migration
{
    /**
     * 世帯名の末尾に付ける文字列（世帯名の初期値は「{ユーザー名}の家計簿」）
     */
    private const NAME_SUFFIX = 'の家計簿';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既存行を埋めるまではNULLを許可した状態で列を追加する
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('household_id')->nullable()->comment('所属する世帯のID')->after('id');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('household_id')->nullable()->comment('所有する世帯のID')->after('id');
        });

        // 既存のユーザーごとに1人世帯を作り、ユーザーとその収支を世帯にひも付ける
        DB::transaction(function () {
            DB::table('users')->orderBy('id')->lazyById()->each(function (object $user) {
                $householdId = DB::table('households')->insertGetId([
                    'name' => $this->householdName($user->name),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')->where('id', $user->id)->update(['household_id' => $householdId]);
                DB::table('transactions')->where('user_id', $user->id)->update(['household_id' => $householdId]);
            });
        });

        // すべての行が埋まったので必須にし、外部キーを張る。
        // 世帯は削除を制限する（メンバーや収支が残っている世帯を消せないようにする）
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('household_id')->nullable(false)->comment('所属する世帯のID')->change();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('household_id')->nullable(false)->comment('所有する世帯のID')->change();
            $table->foreign('household_id')->references('id')->on('households')->restrictOnDelete();

            // 一覧表示・月次フィルタ用と、種別フィルタ用のインデックス（user_id版と同じ構成）
            $table->index(['household_id', 'date']);
            $table->index(['household_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 外部キーは対応するインデックスより先に外す
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['household_id']);
            $table->dropIndex(['household_id', 'date']);
            $table->dropIndex(['household_id', 'type']);
            $table->dropColumn('household_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['household_id']);
            $table->dropColumn('household_id');
        });

        // ひも付けを外したあとに世帯を消す（このマイグレーションだけを戻して再実行しても、世帯が重複しないように）
        DB::table('households')->delete();
    }

    /**
     * ユーザー名から世帯名の初期値を作る（世帯名の列の長さ255文字に収まるよう、ユーザー名を切り詰める）
     */
    private function householdName(string $userName): string
    {
        return mb_substr($userName, 0, 255 - mb_strlen(self::NAME_SUFFIX)).self::NAME_SUFFIX;
    }
};
