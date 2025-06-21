<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // 駕駛姓名
            $table->string('phone')->unique();       // 手機號碼
            $table->string('id_number')->unique();   // 身分證字號
            $table->string('fleet_number')->nullable(); // 所屬車隊編號
            $table->string('plate_number')->nullable(); // 車牌號碼
            $table->string('car_color')->nullable(); // 車色
            $table->string('car_brand')->nullable(); // 車品牌
            $table->string('car_Vehicle_Style')->nullable(); // 車輛樣式
            $table->string('lc_company')->nullable(); //長照屬於哪家公司
            $table->string('order_type')->nullable(); //可接訂單種類EX.長照、復康..
            $table->string('service_type')->nullable(); //可服務類類型EX.交通、爬梯機
            $table->string('status')->default('active'); // 狀態：active, inactive, blacklist
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
