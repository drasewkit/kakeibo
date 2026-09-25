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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index()->comment('キュー名');
            $table->longText('payload')->comment('ジョブのペイロード');
            $table->unsignedSmallInteger('attempts')->comment('試行回数');
            $table->unsignedInteger('reserved_at')->nullable()->comment('処理開始日時（UNIXタイムスタンプ）');
            $table->unsignedInteger('available_at')->comment('実行可能日時（UNIXタイムスタンプ）');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->comment('バッチ名');
            $table->integer('total_jobs')->comment('総ジョブ数');
            $table->integer('pending_jobs')->comment('未処理のジョブ数');
            $table->integer('failed_jobs')->comment('失敗したジョブ数');
            $table->longText('failed_job_ids')->comment('失敗したジョブIDの一覧');
            $table->mediumText('options')->nullable()->comment('バッチオプション');
            $table->integer('cancelled_at')->nullable()->comment('キャンセル日時（UNIXタイムスタンプ）');
            $table->integer('created_at');
            $table->integer('finished_at')->nullable()->comment('完了日時（UNIXタイムスタンプ）');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->comment('ジョブのUUID');
            $table->string('connection')->comment('接続名');
            $table->string('queue')->comment('キュー名');
            $table->longText('payload')->comment('ジョブのペイロード');
            $table->longText('exception')->comment('発生した例外の内容');
            $table->timestamp('failed_at')->useCurrent()->comment('失敗日時');

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
