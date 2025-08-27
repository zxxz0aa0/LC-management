<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 使用原生 SQL 來修改 enum 欄位，避免 Doctrine DBAL 問題
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD') DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 使用原生 SQL 還原為原本的 enum 值
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled') DEFAULT 'open'");
    }
};
