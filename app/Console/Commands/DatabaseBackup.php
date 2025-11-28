<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup
                            {--type=full : 備份類型 (full, tables, schema)}
                            {--tables= : 指定要備份的資料表（逗號分隔）}
                            {--compress : 是否壓縮備份檔案}
                            {--output= : 自訂輸出目錄}';

    protected $description = '資料庫備份命令 - 支援完整備份、特定表備份、結構備份';

    public function handle()
    {
        $type = $this->option('type');
        $compress = $this->option('compress');
        $customOutput = $this->option('output');

        $this->info('開始執行資料庫備份...');
        $this->info("備份類型: {$type}");

        try {
            $backupFile = match ($type) {
                'full' => $this->backupFull($customOutput),
                'tables' => $this->backupTables($this->option('tables'), $customOutput),
                'schema' => $this->backupSchema($customOutput),
                default => throw new \InvalidArgumentException("不支援的備份類型: {$type}")
            };

            if ($compress) {
                $this->info('正在壓縮備份檔案...');
                $backupFile = $this->compressBackup($backupFile);
            }

            // 清理舊備份（60 天前）
            $this->cleanOldBackups();

            $fileSize = $this->formatBytes(File::size($backupFile));
            $this->info('✓ 備份完成！');
            $this->info("檔案位置: {$backupFile}");
            $this->info("檔案大小: {$fileSize}");

            Log::info('Database backup completed successfully', [
                'file' => $backupFile,
                'size' => $fileSize,
                'type' => $type,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('✗ 備份失敗: '.$e->getMessage());
            Log::error('Database backup failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function backupFull(?string $customOutput = null): string
    {
        $outputDir = $customOutput ?? storage_path('backups/daily');
        $this->ensureDirectoryExists($outputDir);

        if (! $this->checkDiskSpace($outputDir)) {
            throw new \RuntimeException('磁碟空間不足！');
        }

        $timestamp = now()->format('Y_m_d_His');
        $filename = "lc_management_{$timestamp}.sql";
        $outputPath = "{$outputDir}/{$filename}";

        $config = config('database.connections.mysql');

        // 取得 mysqldump 路徑（支援 Windows XAMPP）
        $mysqldumpPath = $this->getMysqldumpPath();

        $command = sprintf(
            '"%s" --defaults-file=%s --host=%s --port=%s --single-transaction --skip-lock-tables --routines --triggers --events %s > "%s" 2>&1',
            $mysqldumpPath,
            $this->createMyCnf($config),
            $config['host'],
            $config['port'],
            $config['database'],
            $outputPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            if (File::exists($outputPath)) {
                File::delete($outputPath);
            }
            $errorMsg = implode("\n", $output);
            throw new \RuntimeException("mysqldump 執行失敗: {$errorMsg}");
        }

        // 驗證備份檔案
        if (! File::exists($outputPath) || File::size($outputPath) < 1024) {
            throw new \RuntimeException('備份檔案無效或太小');
        }

        return $outputPath;
    }

    protected function backupTables(string $tables, ?string $customOutput = null): string
    {
        if (empty($tables)) {
            throw new \InvalidArgumentException('請使用 --tables 參數指定要備份的資料表');
        }

        $outputDir = $customOutput ?? storage_path('backups/critical');
        $this->ensureDirectoryExists($outputDir);

        $timestamp = now()->format('Y_m_d_His');
        $filename = "lc_management_tables_{$timestamp}.sql";
        $outputPath = "{$outputDir}/{$filename}";

        $config = config('database.connections.mysql');
        $mysqldumpPath = $this->getMysqldumpPath();

        // 將表格名稱轉換為空格分隔
        $tableList = str_replace(',', ' ', $tables);

        $command = sprintf(
            '"%s" --defaults-file=%s --host=%s --port=%s --single-transaction %s %s > "%s" 2>&1',
            $mysqldumpPath,
            $this->createMyCnf($config),
            $config['host'],
            $config['port'],
            $config['database'],
            $tableList,
            $outputPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMsg = implode("\n", $output);
            throw new \RuntimeException("mysqldump 執行失敗: {$errorMsg}");
        }

        return $outputPath;
    }

    protected function backupSchema(?string $customOutput = null): string
    {
        $outputDir = $customOutput ?? storage_path('backups/schema');
        $this->ensureDirectoryExists($outputDir);

        $filename = 'lc_management_schema_'.now()->format('Y_m_d').'.sql';
        $outputPath = "{$outputDir}/{$filename}";

        $config = config('database.connections.mysql');
        $mysqldumpPath = $this->getMysqldumpPath();

        $command = sprintf(
            '"%s" --defaults-file=%s --host=%s --port=%s --no-data --routines --triggers --events %s > "%s" 2>&1',
            $mysqldumpPath,
            $this->createMyCnf($config),
            $config['host'],
            $config['port'],
            $config['database'],
            $outputPath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMsg = implode("\n", $output);
            throw new \RuntimeException("mysqldump 執行失敗: {$errorMsg}");
        }

        return $outputPath;
    }

    protected function compressBackup(string $filePath): string
    {
        $gzipPath = $filePath.'.gz';

        if (File::exists($gzipPath)) {
            File::delete($gzipPath);
        }

        // 使用 PHP 原生函數壓縮，確保 Windows 相容性
        $this->info('使用 PHP 原生 gzip 壓縮...');

        $content = File::get($filePath);
        $compressedContent = gzencode($content, 9);

        if ($compressedContent === false) {
            throw new \RuntimeException('壓縮備份檔案失敗：gzencode 執行錯誤');
        }

        $bytesWritten = File::put($gzipPath, $compressedContent);

        if ($bytesWritten === false || ! File::exists($gzipPath)) {
            throw new \RuntimeException('壓縮備份檔案失敗：無法寫入壓縮檔');
        }

        // 刪除原始未壓縮檔案
        File::delete($filePath);

        // 顯示壓縮率
        $originalSize = strlen($content);
        $compressedSize = File::size($gzipPath);
        $ratio = round((1 - $compressedSize / $originalSize) * 100, 1);
        $this->info("壓縮率: {$ratio}% (原始: {$this->formatBytes($originalSize)} → 壓縮後: {$this->formatBytes($compressedSize)})");

        return $gzipPath;
    }

    protected function cleanOldBackups(): void
    {
        $backupDir = storage_path('backups/daily');

        if (! File::exists($backupDir)) {
            return;
        }

        $files = File::files($backupDir);
        $cutoffTime = now()->subDays(60)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoffTime) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("已清理 {$deletedCount} 個超過 60 天的舊備份檔案");
        }
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    protected function checkDiskSpace(string $directory): bool
    {
        $freeSpace = disk_free_space($directory);
        $requiredSpace = 500 * 1024 * 1024; // 500MB

        return $freeSpace > $requiredSpace;
    }

    protected function getMysqldumpPath(): string
    {
        // 檢查常見的 mysqldump 位置
        $possiblePaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',  // XAMPP Windows
            '/usr/bin/mysqldump',                     // Linux
            '/usr/local/bin/mysqldump',               // macOS
            'mysqldump',                              // PATH
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // 最後嘗試使用 PATH 中的 mysqldump
        return 'mysqldump';
    }

    protected function createMyCnf(array $config): string
    {
        $cnfPath = storage_path('app/.my.cnf');
        $content = "[client]\n";
        $content .= "user={$config['username']}\n";
        $content .= "password=\"{$config['password']}\"\n";
        $content .= "host={$config['host']}\n";
        $content .= "port={$config['port']}\n";

        File::put($cnfPath, $content);

        // Windows 不支援 chmod，加入錯誤處理
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($cnfPath, 0600);
        }

        return $cnfPath;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
