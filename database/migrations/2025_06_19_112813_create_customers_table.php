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
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // 個案名稱
        $table->string('id_number'); // 身分證字號
        $table->date('birthday')->nullable(); // 生日
        $table->string('gender')->nullable(); // 性別
        $table->json('phone_number'); // 多筆電話，如 ["0912xxxxxx", "02-xxxxxxx"]
        $table->json('addresses'); // 多筆地址，如 ["台北市信義區市府路45號", "新北市板橋區文化路一段"]
        $table->string('contact_person')->nullable(); // 聯絡人
        $table->string('contact_phone')->nullable(); // 聯絡電話
        $table->string('contact_relationship')->nullable(); // 聯絡人關係
        $table->string('email')->unique()->nullable()->default(null); // 電子郵件，唯一
        $table->string('wheelchair')->nullable(); // 是否有輪椅
        $table->string('stair_climbing_machine')->nullable(); // 是否有爬梯機
        $table->string('ride_sharing')->nullable(); // 是否共乘
        $table->string('identity')->nullable(); // 身份別
        $table->text('note')->nullable(); // 備註
        $table->string('a_mechanism')->nullable(); // A單位
        $table->string('a_manager')->nullable(); // 個管師
        $table->string('special_status')->nullable(); // 特殊狀態，例如黑名單
        $table->string('status')->default('active'); // 狀態，預設為 active
        $table->string('created_by')->nullable(); // 建立者
        $table->string('updated_by')->nullable(); // 更新者
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
