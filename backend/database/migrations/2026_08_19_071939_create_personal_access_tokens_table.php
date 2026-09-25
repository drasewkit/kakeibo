<?php

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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            // tokenable_type, tokenable_id の2カラムを生成する複合ヘルパーのため
            // 個別のcomment()は付けられない
            $table->morphs('tokenable');
            $table->text('name')->comment('トークン名');
            $table->string('token', 64)->unique()->comment('トークン本体（ハッシュ化して保存）');
            $table->text('abilities')->nullable()->comment('許可する権限（スコープ）');
            $table->timestamp('last_used_at')->nullable()->comment('最終利用日時');
            $table->timestamp('expires_at')->nullable()->index()->comment('有効期限');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
