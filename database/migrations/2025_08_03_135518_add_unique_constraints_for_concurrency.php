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
            // 檢查是否已存在訂單編號唯一約束，如果沒有則添加
            // 註：order_number 可能已經有唯一約束

            // 為重複訂單檢查添加複合索引，提升查詢效能
            $table->index(['customer_id', 'ride_date', 'ride_time'], 'orders_customer_datetime_index');

            // 為共乘群組查詢添加索引
            $table->index(['carpool_group_id', 'is_group_dissolved'], 'orders_carpool_group_index');

            // 為日期查詢添加索引
            $table->index('ride_date', 'orders_ride_date_index');

            // 為狀態查詢添加索引
            $table->index('status', 'orders_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 移除索引（不移除可能已存在的order_number唯一約束）
            $table->dropIndex('orders_customer_datetime_index');
            $table->dropIndex('orders_carpool_group_index');
            $table->dropIndex('orders_ride_date_index');
            $table->dropIndex('orders_status_index');
        });
    }
};
