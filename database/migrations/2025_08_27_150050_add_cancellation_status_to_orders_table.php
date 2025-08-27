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
            // 修改 status enum 欄位，新增細分的取消狀態
            $table->enum('status', [
                'open',           // 可派遣
                'assigned',       // 已指派
                'bkorder',        // 已候補
                'blocked',        // 黑名單
                'cancelled',      // 一般取消
                'cancelledOOC',   // 別家有車
                'cancelledNOC',   // !取消
                'cancelledCOTD'   // X取消
            ])->default('open')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 還原為原本的 enum 值
            $table->enum('status', [
                'open', 
                'assigned', 
                'bkorder',  
                'blocked', 
                'cancelled'
            ])->default('open')->change();
        });
    }
};
