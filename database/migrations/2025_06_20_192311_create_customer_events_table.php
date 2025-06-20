<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');     // 關聯的客戶
            $table->dateTime('event_date')->nullable();    // 事件發生或建檔時間
            $table->string('event');                        // 事件內容
            $table->unsignedBigInteger('created_by');       // 建立人（user id）
            $table->timestamps();

            // 設定外鍵關聯
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_events');
    }
};
