<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyBackup extends Command
{
    protected $signature = 'db:verify-backup
                            {file : 要驗證的備份檔案路徑}
                            {--detailed : 顯示詳細驗證結果}';

    protected $description = '驗證備份檔案的完整性和結構';

    protected array $requiredTables = [
        'users',
        'orders',
        'order_sequences',
        'customers',
        'drivers',
        'landmarks',
    ];

    public function handle()
    {
        $backupFile = $this->argument('file');
        $detailed = $this->option('detailed');

        $this->info("驗證備份檔案: {$backupFile}");
        $this->newLine();

        $checks = [
            '檔案存在' => $this->checkFileExists($backupFile),
            '檔案大小' => $this->checkFileSize($backupFile),
            '檔案可讀取' => $this->checkFileReadable($backupFile),
            'SQL 結構' => $this->checkSqlStructure($backupFile),
            '關鍵資料表' => $this->checkRequiredTables($backupFile, $detailed),
        ];

        // 顯示結果
        $this->newLine();
        $this->info('=== 驗證結果 ===');
        $allPassed = true;

        foreach ($checks as $check => $result) {
            $status = $result['passed'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("{$status} {$check}: {$result['message']}");

            if (!$result['passed']) {
                $allPassed = false;
            }
        }

        $this->newLine();
        if ($allPassed) {
            $this->info('✓ 所有檢查都通過！備份檔案有效。');
            return Command::SUCCESS;
        } else {
            $this->error('✗ 部分檢查失敗，請檢查備份檔案。');
            return Command::FAILURE;
        }
    }

    protected function checkFileExists(string $file): array
    {
        $exists = File::exists($file);
        return [
            'passed' => $exists,
            'message' => $exists ? '檔案已找到' : '檔案不存在',
        ];
    }

    protected function checkFileSize(string $file): array
    {
        if (!File::exists($file)) {
            return ['passed' => false, 'message' => '檔案不存在'];
        }

        $size = File::size($file);
        $minSize = 1000; // 1 KB
        $maxSize = 10 * 1024 * 1024 * 1024; // 10 GB

        $passed = $size >= $minSize && $size <= $maxSize;
        $message = $this->formatBytes($size);

        if (!$passed) {
            if ($size < $minSize) {
                $message .= ' (檔案太小，可能已損壞)';
            } else {
                $message .= ' (檔案太大)';
            }
        }

        return [
            'passed' => $passed,
            'message' => $message,
        ];
    }

    protected function checkFileReadable(string $file): array
    {
        if (!File::exists($file)) {
            return ['passed' => false, 'message' => '檔案不存在'];
        }

        $readable = is_readable($file);
        return [
            'passed' => $readable,
            'message' => $readable ? '檔案可讀取' : '檔案無法讀取',
        ];
    }

    protected function checkSqlStructure(string $file): array
    {
        if (!File::exists($file)) {
            return ['passed' => false, 'message' => '檔案不存在'];
        }

        try {
            // 讀取檔案內容（支援壓縮檔）
            $content = $this->readFileContent($file);

            // 檢查 SQL 檔案的基本結構（支援 MySQL 和 MariaDB）
            $hasCreateTable = str_contains($content, 'CREATE TABLE');
            $hasDumpHeader = str_contains($content, 'MySQL dump')
                          || str_contains($content, 'MariaDB dump')
                          || str_contains($content, 'mysqldump');

            $passed = $hasCreateTable && $hasDumpHeader;
            $details = [];

            if (!$hasCreateTable) {
                $details[] = '未找到 CREATE TABLE 語句';
            }
            if (!$hasDumpHeader) {
                $details[] = '不是 mysqldump/mariadb-dump 產生的檔案';
            }

            $message = $passed ? '有效的 SQL 結構' : implode(', ', $details);

            return [
                'passed' => $passed,
                'message' => $message,
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '讀取檔案錯誤: ' . $e->getMessage(),
            ];
        }
    }

    protected function checkRequiredTables(string $file, bool $detailed): array
    {
        if (!File::exists($file)) {
            return ['passed' => false, 'message' => '檔案不存在'];
        }

        try {
            $content = $this->readFileContent($file);
            $foundTables = [];
            $missingTables = [];

            foreach ($this->requiredTables as $table) {
                // 使用更精確的正則表達式匹配 CREATE TABLE 語句
                // 格式: CREATE TABLE `table_name` 或 CREATE TABLE IF NOT EXISTS `table_name`
                if (preg_match("/CREATE TABLE[^\`]*\`{$table}\`/i", $content)) {
                    $foundTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }

            $passed = empty($missingTables);
            $message = sprintf(
                '%d/%d 資料表已找到',
                count($foundTables),
                count($this->requiredTables)
            );

            if ($detailed && !empty($missingTables)) {
                $message .= ' (缺少: ' . implode(', ', $missingTables) . ')';
            }

            if ($detailed && !empty($foundTables)) {
                $this->newLine();
                $this->info('找到的資料表:');
                foreach ($foundTables as $table) {
                    $this->line("  <fg=green>✓</> {$table}");
                }
            }

            return [
                'passed' => $passed,
                'message' => $message,
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '檢查資料表錯誤: ' . $e->getMessage(),
            ];
        }
    }

    protected function readFileContent(string $file): string
    {
        // 如果是壓縮檔，解壓縮後讀取
        if (str_ends_with($file, '.gz')) {
            $compressedContent = File::get($file);

            // 解壓縮整個檔案
            $decompressedContent = @gzdecode($compressedContent);

            if ($decompressedContent === false) {
                throw new \RuntimeException('無法解壓縮檔案');
            }

            return $decompressedContent;
        }

        // 一般檔案，讀取整個檔案
        return File::get($file);
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
