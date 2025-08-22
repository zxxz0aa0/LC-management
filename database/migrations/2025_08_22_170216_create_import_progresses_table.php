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
        Schema::create('import_progresses', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->string('type')->default('customers'); // 匯入類型
            $table->string('filename'); // 檔案名稱
            $table->integer('total_rows')->default(0); // 總行數
            $table->integer('processed_rows')->default(0); // 已處理行數
            $table->integer('success_count')->default(0); // 成功筆數
            $table->integer('error_count')->default(0); // 錯誤筆數
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('error_messages')->nullable(); // 錯誤訊息
            $table->timestamp('started_at')->nullable(); // 開始時間
            $table->timestamp('completed_at')->nullable(); // 完成時間
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_progresses');
    }
};
