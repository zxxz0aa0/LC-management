<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MonitorBackups extends Command
{
    protected $signature = 'db:monitor-backups
                            {--report : 生成詳細報告}';

    protected $description = '監控備份狀態並檢查潛在問題';

    protected array $checks = [];

    public function handle()
    {
        $this->info("開始監控備份狀態...\n");

        $backupDir = storage_path('backups');

        if (!File::exists($backupDir)) {
            $this->error("✗ 備份目錄不存在: {$backupDir}");
            return Command::FAILURE;
        }

        // 執行各項檢查
        $this->checks = [
            '最近備份時間' => $this->checkRecentBackup($backupDir),
            '磁碟空間' => $this->checkDiskSpace($backupDir),
            '備份數量' => $this->checkBackupCount($backupDir),
            '檔案大小' => $this->checkFileSizes($backupDir),
        ];

        // 顯示檢查結果
        $this->displayResults();

        // 如果要求詳細報告
        if ($this->option('report')) {
            $this->generateDetailedReport($backupDir);
        }

        // 記錄監控結果
        $allPassed = collect($this->checks)->every(fn($check) => $check['passed']);

        if ($allPassed) {
            Log::info('Backup monitoring: All checks passed');
            return Command::SUCCESS;
        } else {
            Log::warning('Backup monitoring: Some checks failed', $this->checks);
            return Command::FAILURE;
        }
    }

    protected function checkRecentBackup(string $backupDir): array
    {
        $dailyDir = $backupDir . '/daily';

        if (!File::exists($dailyDir)) {
            return ['passed' => false, 'message' => 'Daily backup directory not found'];
        }

        $files = File::files($dailyDir);

        if (empty($files)) {
            return ['passed' => false, 'message' => 'No backup files found'];
        }

        // 取得最新的備份檔案
        usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
        $latestFile = $files[0];
        $hoursSinceBackup = (time() - $latestFile->getMTime()) / 3600;

        // 檢查是否在 26 小時內有備份（允許一些延遲）
        $passed = $hoursSinceBackup < 26;
        $message = sprintf(
            'Latest backup: %s (%.1f hours ago)',
            $latestFile->getFilename(),
            $hoursSinceBackup
        );

        return ['passed' => $passed, 'message' => $message];
    }

    protected function checkDiskSpace(string $backupDir): array
    {
        $freeSpace = disk_free_space($backupDir);
        $totalSpace = disk_total_space($backupDir);
        $usedPercent = (1 - $freeSpace / $totalSpace) * 100;

        $passed = $usedPercent < 90;
        $message = sprintf(
            'Free: %s / Total: %s (%.1f%% used)',
            $this->formatBytes($freeSpace),
            $this->formatBytes($totalSpace),
            $usedPercent
        );

        return ['passed' => $passed, 'message' => $message];
    }

    protected function checkBackupCount(string $backupDir): array
    {
        $counts = [];
        foreach (['daily', 'manual'] as $type) {
            $dir = $backupDir . '/' . $type;
            if (File::exists($dir)) {
                $counts[$type] = count(File::files($dir));
            } else {
                $counts[$type] = 0;
            }
        }

        $passed = $counts['daily'] > 0;
        $message = sprintf(
            'Daily: %d, Manual: %d',
            $counts['daily'],
            $counts['manual']
        );

        return ['passed' => $passed, 'message' => $message];
    }

    protected function checkFileSizes(string $backupDir): array
    {
        $dailyDir = $backupDir . '/daily';

        if (!File::exists($dailyDir)) {
            return ['passed' => false, 'message' => 'Daily backup directory not found'];
        }

        $files = File::files($dailyDir);

        if (empty($files)) {
            return ['passed' => false, 'message' => 'No backup files found'];
        }

        $sizes = array_map(fn($file) => File::size($file), $files);
        $avgSize = array_sum($sizes) / count($sizes);

        // 取得最新檔案
        usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
        $latestSize = File::size($files[0]);

        // 檢查最新備份大小是否異常（與平均值差異超過 50%）
        if ($avgSize > 0) {
            $deviation = abs($latestSize - $avgSize) / $avgSize;
            $passed = $deviation < 0.5;
        } else {
            $passed = true;
            $deviation = 0;
        }

        $message = sprintf(
            'Latest: %s, Average: %s',
            $this->formatBytes($latestSize),
            $this->formatBytes($avgSize)
        );

        if (!$passed) {
            $message .= ' (Abnormal size detected)';
        }

        return ['passed' => $passed, 'message' => $message];
    }

    protected function displayResults(): void
    {
        $this->info("檢查結果：\n");

        foreach ($this->checks as $name => $result) {
            $status = $result['passed'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("{$status} {$name}: {$result['message']}");
        }

        $allPassed = collect($this->checks)->every(fn($check) => $check['passed']);

        $this->newLine();

        if ($allPassed) {
            $this->info("所有檢查都通過！");
        } else {
            $this->warn("部分檢查未通過，請檢查備份系統");
        }
    }

    protected function generateDetailedReport(string $backupDir): void
    {
        $this->newLine();
        $this->info("=== 詳細報告 ===\n");

        // 列出各類型備份的詳細資訊
        foreach (['daily', 'manual', 'critical', 'schema'] as $type) {
            $dir = $backupDir . '/' . $type;

            if (!File::exists($dir)) {
                continue;
            }

            $files = File::files($dir);

            if (empty($files)) {
                continue;
            }

            $this->info("[{$type}] 目錄:");
            $this->line("  檔案數量: " . count($files));

            $totalSize = array_sum(array_map(fn($file) => File::size($file), $files));
            $this->line("  總大小: " . $this->formatBytes($totalSize));

            // 列出最新的 3 個備份
            usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
            $recentFiles = array_slice($files, 0, 3);

            $this->line("  最近備份:");
            foreach ($recentFiles as $file) {
                $size = $this->formatBytes(File::size($file));
                $time = date('Y-m-d H:i:s', $file->getMTime());
                $this->line("    - {$file->getFilename()} ({$size}, {$time})");
            }

            $this->newLine();
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
