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
        Schema::table('dispatch_records', function (Blueprint $table) {
            // 登打處理狀態
            $table->enum('entry_status', ['pending', 'processing', 'completed'])
                ->default('pending')
                ->after('notes')
                ->comment('登打處理狀態：pending=未處理, processing=處理中, completed=處理完畢');

            // 最後更新者
            $table->unsignedBigInteger('entry_status_updated_by')->nullable()->after('entry_status');

            // 最後更新時間
            $table->timestamp('entry_status_updated_at')->nullable()->after('entry_status_updated_by');

            // 建立索引
            $table->index('entry_status');

            // 建立外鍵約束
            $table->foreign('entry_status_updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_records', function (Blueprint $table) {
            // 移除外鍵約束
            $table->dropForeign(['entry_status_updated_by']);

            // 移除索引
            $table->dropIndex(['entry_status']);

            // 移除欄位
            $table->dropColumn(['entry_status', 'entry_status_updated_by', 'entry_status_updated_at']);
        });
    }
};
