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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('dispatch_record_id')->nullable()->after('updated_by')->comment('排趟記錄ID');
            $table->index('dispatch_record_id');
            $table->foreign('dispatch_record_id')
                ->references('id')->on('dispatch_records')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dispatch_record_id']);
            $table->dropIndex(['dispatch_record_id']);
            $table->dropColumn('dispatch_record_id');
        });
    }
};
