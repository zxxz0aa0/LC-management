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
        // 修改 status 欄位的 ENUM 值，新增 blacklist 選項
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'blacklist') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回復原本的 ENUM 值（移除 blacklist）
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled') NOT NULL DEFAULT 'open'");
    }
};
