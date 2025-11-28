<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListBackups extends Command
{
    protected $signature = 'db:list-backups
                            {--type= : 篩選備份類型 (daily, manual, critical, schema)}
                            {--limit=10 : 顯示的備份數量}';

    protected $description = '列出所有可用的備份檔案';

    public function handle()
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');

        $backupDir = storage_path('backups');

        if (! File::exists($backupDir)) {
            $this->error("備份目錄不存在: {$backupDir}");

            return Command::FAILURE;
        }

        $this->info("=== LC-management 備份檔案列表 ===\n");

        $types = $type ? [$type] : ['daily', 'manual', 'critical', 'schema'];
        $totalFiles = 0;
        $totalSize = 0;

        foreach ($types as $backupType) {
            $dir = $backupDir.'/'.$backupType;

            if (! File::exists($dir)) {
                continue;
            }

            $files = File::files($dir);

            if (empty($files)) {
                continue;
            }

            // 按修改時間排序（最新的在前）
            usort($files, fn ($a, $b) => $b->getMTime() <=> $a->getMTime());

            $this->info("[{$backupType}] 目錄 ({$dir})");
            $this->line(str_repeat('-', 80));

            $displayCount = min(count($files), $limit);
            $displayedFiles = array_slice($files, 0, $displayCount);

            $tableData = [];
            foreach ($displayedFiles as $file) {
                $size = File::size($file);
                $totalSize += $size;
                $totalFiles++;

                $tableData[] = [
                    'filename' => $file->getFilename(),
                    'size' => $this->formatBytes($size),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }

            $this->table(
                ['檔案名稱', '大小', '建立時間'],
                $tableData
            );

            if (count($files) > $limit) {
                $remaining = count($files) - $limit;
                $this->line("  ... 還有 {$remaining} 個檔案未顯示");
            }

            $this->newLine();
        }

        // 顯示統計摘要
        $this->info('=== 統計摘要 ===');
        $this->line("總檔案數: {$totalFiles}");
        $this->line('總大小: '.$this->formatBytes($totalSize));

        // 顯示磁碟空間資訊
        $freeSpace = disk_free_space($backupDir);
        $this->line('可用磁碟空間: '.$this->formatBytes($freeSpace));

        return Command::SUCCESS;
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
