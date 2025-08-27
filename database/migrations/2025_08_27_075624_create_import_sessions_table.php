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
        Schema::create('import_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique(); // 會話唯一標識
            $table->string('type')->default('customers'); // 匯入類型
            $table->string('filename'); // 原始檔案名
            $table->string('file_path')->nullable(); // 檔案儲存路徑
            $table->integer('total_rows')->default(0); // 總行數
            $table->integer('processed_rows')->default(0); // 已處理行數
            $table->integer('success_count')->default(0); // 成功筆數
            $table->integer('error_count')->default(0); // 錯誤筆數
            $table->json('error_messages')->nullable(); // 錯誤訊息
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending'); // 狀態
            $table->timestamp('started_at')->nullable(); // 開始時間
            $table->timestamp('completed_at')->nullable(); // 完成時間
            $table->unsignedBigInteger('created_by')->nullable(); // 建立者ID
            $table->timestamps();

            // 索引
            $table->index('session_id');
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_sessions');
    }
};
