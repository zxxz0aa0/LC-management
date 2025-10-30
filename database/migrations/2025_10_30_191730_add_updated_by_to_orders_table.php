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
        Schema::table('orders', function (Blueprint $table) {
            // 在 created_by 之後新增 updated_by 欄位
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->foreign('updated_by')
                ->references('id')->on('users')
                ->onDelete('set null'); // 使用者刪除時，設為 null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 移除外鍵約束和欄位
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }
};
