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
        // 世帯（家計簿を共有する単位）。収支はユーザーではなく世帯が所有する
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('世帯名');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
