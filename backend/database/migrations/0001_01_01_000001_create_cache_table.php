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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('キャッシュキー');
            $table->mediumText('value')->comment('キャッシュ値');
            $table->bigInteger('expiration')->index()->comment('有効期限（UNIXタイムスタンプ）');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('ロックキー');
            $table->string('owner')->comment('ロック取得者の識別子');
            $table->bigInteger('expiration')->index()->comment('有効期限（UNIXタイムスタンプ）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
