<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // 將 status 欄位改為 enum 並預設開案中
            $table->enum('status', ['開案中', '暫停中', '已結案'])
                ->default('開案中')
                ->comment('狀態：開案中、暫停中、已結案')
                ->change();
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            // 如需回復到原本的 string 欄位
            $table->string('status')
                ->default('active')
                ->comment('')
                ->change();
        });
    }
};
