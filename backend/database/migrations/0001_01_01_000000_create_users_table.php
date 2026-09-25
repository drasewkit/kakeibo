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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名前');
            $table->string('email')->unique()->comment('メールアドレス（ログインID）');
            $table->timestamp('email_verified_at')->nullable()->comment('メール確認日時');
            $table->string('password')->comment('パスワード（ハッシュ化して保存）');
            $table->rememberToken()->comment('ログイン保持用トークン');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('対象のメールアドレス');
            $table->string('token')->comment('リセット用トークン');
            $table->timestamp('created_at')->nullable()->comment('発行日時');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index()->comment('ログイン中のユーザーID（未ログイン時はNULL）');
            $table->string('ip_address', 45)->nullable()->comment('接続元IPアドレス');
            $table->text('user_agent')->nullable()->comment('ユーザーエージェント');
            $table->longText('payload')->comment('セッションデータ本体');
            $table->integer('last_activity')->index()->comment('最終アクセス日時（UNIXタイムスタンプ）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
