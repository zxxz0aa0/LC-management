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
        Schema::table('import_progresses', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('filename'); // 存儲實際檔案路徑
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_progresses', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
