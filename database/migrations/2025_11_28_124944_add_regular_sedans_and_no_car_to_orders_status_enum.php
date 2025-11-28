<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 修改 status 欄位的 ENUM 值，新增 regular_sedans 和 no_car
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD', 'blacklist', 'no_send', 'regular_sedans', 'no_car') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回復到上一個版本的 ENUM 值
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD', 'blacklist', 'no_send') NOT NULL DEFAULT 'open'");
    }
};
