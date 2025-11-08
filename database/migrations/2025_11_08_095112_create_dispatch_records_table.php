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
        Schema::create('dispatch_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->unique()->comment('批次識別碼');
            $table->string('dispatch_name', 200)->comment('排趟名稱：日期+時間+車隊編號+使用者名稱');

            // 司機資訊（快照）
            $table->unsignedBigInteger('driver_id')->nullable()->comment('司機ID');
            $table->string('driver_name', 100)->comment('司機姓名快照');
            $table->string('driver_fleet_number', 50)->nullable()->comment('隊編快照');

            // 訂單資訊
            $table->json('order_ids')->comment('訂單ID陣列');
            $table->integer('order_count')->default(0)->comment('訂單數量');
            $table->json('order_details')->comment('訂單詳細資訊快照');
            $table->date('dispatch_date')->nullable()->comment('排趟日期（主要服務日期）');

            // 執行人員與時間
            $table->unsignedBigInteger('performed_by')->comment('執行人 user_id');
            $table->timestamp('performed_at')->comment('執行時間');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            $table->timestamps();

            // 索引
            $table->index('batch_id');
            $table->index('driver_id');
            $table->index('dispatch_date');
            $table->index('performed_by');
            $table->index('performed_at');

            // 外鍵
            $table->foreign('driver_id')
                ->references('id')->on('drivers')
                ->onDelete('set null');
            $table->foreign('performed_by')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_records');
    }
};
