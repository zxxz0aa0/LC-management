<?php

namespace App\Console\Commands;

use App\Models\DispatchRecord;
use Illuminate\Console\Command;

class CleanOldDispatchRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:clean {--months=2 : 保留幾個月的記錄}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理超過指定月份的排趟記錄（預設保留 2 個月）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $months = $this->option('months');
        $cutoffDate = now()->subMonths($months);

        $this->info("開始清理 {$months} 個月前的排趟記錄...");
        $this->info("刪除日期早於：{$cutoffDate->format('Y-m-d H:i:s')} 的記錄");

        // 查詢要刪除的記錄
        $oldRecords = DispatchRecord::where('performed_at', '<', $cutoffDate)->get();
        $count = $oldRecords->count();

        if ($count === 0) {
            $this->info('沒有需要清理的記錄。');

            return 0;
        }

        // 顯示確認提示（只在非靜默模式下）
        if (! $this->option('quiet')) {
            $this->warn("即將刪除 {$count} 筆記錄。");
            if (! $this->confirm('確定要繼續嗎？')) {
                $this->info('已取消操作。');

                return 1;
            }
        }

        // 執行刪除
        $deletedCount = 0;
        $this->output->progressStart($count);

        foreach ($oldRecords as $record) {
            $record->delete();
            $deletedCount++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("成功清理 {$deletedCount} 筆排趟記錄。");

        return 0;
    }
}
