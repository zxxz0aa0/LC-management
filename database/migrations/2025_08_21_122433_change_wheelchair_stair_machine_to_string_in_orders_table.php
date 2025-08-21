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
        // 先修改欄位類型為 string
        Schema::table('orders', function (Blueprint $table) {
            $table->string('wheelchair')->default('否')->change();
            $table->string('stair_machine')->default('否')->change();
        });

        // 然後更新現有資料：將 boolean 值轉換為對應的文字
        DB::table('orders')->where('wheelchair', '1')->update(['wheelchair' => '是']);
        DB::table('orders')->where('wheelchair', '0')->update(['wheelchair' => '否']);

        DB::table('orders')->where('stair_machine', '1')->update(['stair_machine' => '是']);
        DB::table('orders')->where('stair_machine', '0')->update(['stair_machine' => '否']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 先更新現有資料：將文字值轉換回 boolean
        DB::table('orders')->where('wheelchair', '是')->update(['wheelchair' => true]);
        DB::table('orders')->where('wheelchair', '否')->update(['wheelchair' => false]);

        DB::table('orders')->where('stair_machine', '是')->update(['stair_machine' => true]);
        DB::table('orders')->where('stair_machine', '否')->update(['stair_machine' => false]);

        // 然後修改欄位類型回 boolean
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('wheelchair')->default(false)->change();
            $table->boolean('stair_machine')->default(false)->change();
        });
    }
};
