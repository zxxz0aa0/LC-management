<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id'); // 主鍵

            $table->string('order_number')->unique(); // 訂單編號（唯一）

            $table->unsignedBigInteger('customer_id'); // 客戶 ID（關聯 customers 表）
            $table->unsignedBigInteger('driver_id')->nullable(); // 隊員（駕駛）ID，尚未搶單時可為 null
            $table->foreign('customer_id')
                ->references('id')->on('customers');

            $table->foreign('driver_id')
                ->references('id')->on('drivers')
                ->onDelete('set null');// 駕駛刪除時，設為 null（不會刪訂單）

            $table->string('customer_name');
            $table->string('customer_id_number');
            $table->string('customer_phone');

            $table->string('driver_name')->nullable();
            $table->string('driver_plate_number')->nullable();
            


            $table->string('order_type')->nullable();  // 訂單類型（新北長照、台北長照...）
            $table->string('service_company')->nullable();    // 服務單位（太豐、大立亨）

            $table->date('ride_date'); // 用車日期
            $table->time('ride_time'); // 用車時間

            // 上車地點資訊
            $table->string('pickup_county')->nullable();   // 上車縣市
            $table->string('pickup_district')->nullable(); // 上車區域
            $table->string('pickup_address');              // 上車地址
            $table->decimal('pickup_lat', 10, 7)->nullable(); // 上車點緯度
            $table->decimal('pickup_lng', 10, 7)->nullable(); // 上車點經度

            // 下車地點資訊
            $table->string('dropoff_county')->nullable();   // 下車縣市
            $table->string('dropoff_district')->nullable(); // 下車區域
            $table->string('dropoff_address');              // 下車地址
            $table->decimal('dropoff_lat', 10, 7)->nullable(); // 下車點緯度
            $table->decimal('dropoff_lng', 10, 7)->nullable(); // 下車點經度

            // 搭車需求
            $table->boolean('wheelchair')->default(false); // 是否需要輪椅
            $table->boolean('stair_machine')->default(false); // 是否需要爬梯機
            $table->tinyInteger('companions')->default(0); // 陪同人數

            $table->text('remark')->nullable(); // 備註

            $table->string('created_by'); // 建單人員帳號或名稱
            $table->string('identity')->nullable(); // 身份別（例如 市區-低收 等）
            $table->string('carpool_with')->nullable(); // 共乘對象（可用文字紀錄）
            $table->boolean('special_order')->default(false); // 特別訂單（例如特殊處理）

            $table->enum('status', ['open', 'assigned', 'replacement',  'blocked','cancelled'])->default('open'); // 訂單狀態 open可派、ass已指派、repl已候補、block黑名單、can取消

            $table->timestamps(); // 建立與更新時間（created_at / updated_at）
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
