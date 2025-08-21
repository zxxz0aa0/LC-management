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
            // 群組相關欄位
            $table->string('carpool_group_id', 50)->nullable()->index()->comment('共乘群組ID');
            $table->boolean('is_main_order')->default(true)->index()->comment('是否為主訂單');
            $table->integer('carpool_member_count')->default(1)->comment('群組成員數量');
            $table->string('main_order_number', 50)->nullable()->comment('主訂單編號（用於追蹤）');
            $table->tinyInteger('member_sequence')->nullable()->comment('成員序號');

            // 群組解除相關欄位
            $table->boolean('is_group_dissolved')->default(false)->index()->comment('群組是否已解除');
            $table->timestamp('dissolved_at')->nullable()->comment('群組解除時間');
            $table->string('dissolved_by')->nullable()->comment('解除操作人');
            $table->string('original_group_id', 50)->nullable()->comment('原群組ID（保留歷史記錄）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 移除索引
            $table->dropIndex(['carpool_group_id']);
            $table->dropIndex(['is_main_order']);
            $table->dropIndex(['is_group_dissolved']);

            // 移除欄位
            $table->dropColumn([
                'carpool_group_id',
                'is_main_order',
                'carpool_member_count',
                'main_order_number',
                'member_sequence',
                'is_group_dissolved',
                'dissolved_at',
                'dissolved_by',
                'original_group_id',
            ]);
        });
    }
};
