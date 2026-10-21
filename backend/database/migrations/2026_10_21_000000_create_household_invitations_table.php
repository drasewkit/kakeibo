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
        // 世帯への招待。トークンそのものは保存せず、ハッシュ値で照合する
        Schema::create('household_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->comment('招待先の世帯のID')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->comment('招待したユーザーのID')->constrained('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique()->comment('招待トークンのSHA-256ハッシュ');
            $table->dateTime('expires_at')->comment('有効期限');
            $table->dateTime('used_at')->nullable()->comment('使用された日時（未使用はNULL）');
            $table->foreignId('used_by')->nullable()->comment('招待を使って参加したユーザーのID')->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_invitations');
    }
};
