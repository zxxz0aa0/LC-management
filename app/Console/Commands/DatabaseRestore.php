<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DatabaseRestore extends Command
{
    protected $signature = 'db:restore
                            {file : 備份檔案路徑}
                            {--database= : 還原到指定的資料庫（預設為目前資料庫）}
                            {--force : 強制還原，不詢問確認}';

    protected $description = '從備份檔案還原資料庫';

    public function handle()
    {
        $filePath = $this->argument('file');
        $targetDatabase = $this->option('database') ?? config('database.connections.mysql.database');
        $force = $this->option('force');

        // 驗證備份檔案
        if (!File::exists($filePath)) {
            $this->error("✗ 備份檔案不存在: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("準備還原資料庫");
        $this->info("備份檔案: {$filePath}");
        $this->info("目標資料庫: {$targetDatabase}");

        // 確認還原操作
        if (!$force) {
            if (!$this->confirm('⚠️  警告：此操作將覆蓋現有資料庫。確定要繼續嗎？')) {
                $this->info('還原操作已取消');
                return Command::SUCCESS;
            }
        }

        try {
            $this->info("開始還原資料庫...");

            // 檢查檔案是否為 .gz 壓縮檔
            $isCompressed = str_ends_with($filePath, '.gz');
            $tempFile = null;

            if ($isCompressed) {
                $this->info("檢測到壓縮檔案，正在解壓縮...");
                $tempFile = $this->decompressBackup($filePath);
                $filePath = $tempFile;
            }

            // 驗證 SQL 檔案
            $this->validateSqlFile($filePath);

            // 執行還原
            $this->restoreDatabase($filePath, $targetDatabase);

            // 清理暫存檔
            if ($tempFile && File::exists($tempFile)) {
                File::delete($tempFile);
            }

            $this->info("✓ 資料庫還原完成！");

            Log::info("Database restored successfully", [
                'file' => $this->argument('file'),
                'database' => $targetDatabase
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ 還原失敗: " . $e->getMessage());
            Log::error("Database restore failed: " . $e->getMessage());

            // 清理暫存檔
            if (isset($tempFile) && $tempFile && File::exists($tempFile)) {
                File::delete($tempFile);
            }

            return Command::FAILURE;
        }
    }

    protected function decompressBackup(string $gzipPath): string
    {
        $tempPath = storage_path('app/temp_restore_' . now()->timestamp . '.sql');

        $command = sprintf('gunzip -c "%s" > "%s"', $gzipPath, $tempPath);
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($tempPath)) {
            throw new \RuntimeException('解壓縮備份檔案失敗');
        }

        return $tempPath;
    }

    protected function validateSqlFile(string $filePath): void
    {
        $fileSize = File::size($filePath);

        if ($fileSize < 100) {
            throw new \RuntimeException('SQL 檔案太小，可能已損壞');
        }

        // 檢查檔案前 1000 bytes 是否包含 SQL 語法
        $content = File::get($filePath, false, null, 0, 1000);

        if (!str_contains($content, 'MySQL') && !str_contains($content, 'CREATE') && !str_contains($content, 'INSERT')) {
            throw new \RuntimeException('檔案格式不正確，不是有效的 SQL 備份檔案');
        }
    }

    protected function restoreDatabase(string $filePath, string $database): void
    {
        $config = config('database.connections.mysql');

        // 建立臨時 my.cnf
        $cnfPath = storage_path('app/.my_restore.cnf');
        $content = "[client]\n";
        $content .= "user={$config['username']}\n";
        $content .= "password=\"{$config['password']}\"\n";
        $content .= "host={$config['host']}\n";
        $content .= "port={$config['port']}\n";

        File::put($cnfPath, $content);
        chmod($cnfPath, 0600);

        // 執行還原命令
        $command = sprintf(
            'mysql --defaults-file=%s %s < "%s" 2>&1',
            $cnfPath,
            $database,
            $filePath
        );

        exec($command, $output, $returnCode);

        // 清理 my.cnf
        if (File::exists($cnfPath)) {
            File::delete($cnfPath);
        }

        if ($returnCode !== 0) {
            throw new \RuntimeException('mysql 還原執行失敗: ' . implode("\n", $output));
        }

        // 驗證還原結果
        try {
            $tableCount = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$database])[0]->count;

            if ($tableCount === 0) {
                throw new \RuntimeException('還原後資料庫中沒有資料表，還原可能失敗');
            }

            $this->info("已還原 {$tableCount} 個資料表");

        } catch (\Exception $e) {
            throw new \RuntimeException('無法驗證還原結果: ' . $e->getMessage());
        }
    }
}
