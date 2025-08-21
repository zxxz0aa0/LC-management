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
        Schema::create('landmarks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('地標名稱');
            $table->text('address')->comment('完整地址');
            $table->string('city', 50)->comment('城市');
            $table->string('district', 50)->comment('區域');
            $table->string('category', 50)->default('general')->comment('分類（hospital, clinic, transport, education, government, commercial, general等）');
            $table->text('description')->nullable()->comment('地標描述');
            $table->json('coordinates')->nullable()->comment('座標資訊（可選）');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->integer('usage_count')->default(0)->comment('使用次數統計');
            $table->string('created_by', 100)->nullable()->comment('建立者');
            $table->timestamps();

            // 索引
            $table->index('name');
            $table->index(['city', 'district']);
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landmarks');
    }
};
