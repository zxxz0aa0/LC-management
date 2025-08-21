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
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->string('date_key', 8)->primary()->comment('日期鍵值(YYYYMMDD)');
            $table->integer('sequence_number')->unsigned()->default(0)->comment('當日序列號');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('最後更新時間');

            // 索引優化
            $table->index(['date_key', 'sequence_number'], 'idx_date_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_sequences');
    }
};
