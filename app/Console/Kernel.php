<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 每天凌晨 2 點清理超過 2 個月的排趟記錄
        $schedule->command('dispatch:clean --months=2 --quiet')->dailyAt('02:00');

        // 每日完整備份 - 凌晨 (02:00)
        $schedule->command('db:backup --type=full --compress')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     Log::info('Database backup (02:00) completed successfully');
                 })
                 ->onFailure(function () {
                     Log::error('Database backup (02:00) failed');
                 });

        // 每日完整備份 - 中午 (12:00)
        $schedule->command('db:backup --type=full --compress')
                 ->dailyAt('12:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     Log::info('Database backup (12:00) completed successfully');
                 })
                 ->onFailure(function () {
                     Log::error('Database backup (12:00) failed');
                 });

        // 每日完整備份 - 晚上 (18:00)
        $schedule->command('db:backup --type=full --compress')
                 ->dailyAt('18:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     Log::info('Database backup (18:00) completed successfully');
                 })
                 ->onFailure(function () {
                     Log::error('Database backup (18:00) failed');
                 });

        // 關鍵表額外備份 (每 4 小時)
        $schedule->command('db:backup --type=tables --tables=orders,order_sequences,customers,drivers --compress')
                 ->cron('0 */4 * * *')
                 ->withoutOverlapping()
                 ->runInBackground();

        // 備份狀態監控 (每小時)
        $schedule->command('db:monitor-backups')
                 ->hourly()
                 ->withoutOverlapping();

        // 每日備份報告 (每天早上 9:00)
        $schedule->command('db:monitor-backups --report')
                 ->dailyAt('09:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
