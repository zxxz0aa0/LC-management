<?php

namespace App\Imports;

use App\Models\ImportSession;
use App\Services\CustomerImportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class CustomerImport implements ToCollection, WithChunkReading, WithEvents
{
    use RegistersEventListeners;

    public string $sessionId;
    private CustomerImportService $importService;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->importService = new CustomerImportService();
    }

    public function collection(Collection $rows)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // 啟用垃圾回收機制
        gc_enable();
        
        Log::info('開始客戶匯入批次處理', [
            'session_id' => $this->sessionId,
            'rows_count' => $rows->count(),
            'start_memory' => round($startMemory / 1024 / 1024, 2) . 'MB',
        ]);

        try {
            // 取得匯入會話（通過 session_id UUID 查詢）
            $session = ImportSession::where('session_id', $this->sessionId)->first();
            if (!$session) {
                throw new \Exception("找不到匯入會話 session_id: {$this->sessionId}");
            }

            // 設定匯入服務
            $this->importService->setImportSession($session);

            // 僅在第一次處理時更新狀態為「處理中」
            if ($session->status === 'pending') {
                $session->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            }

            // 處理匯入
            $this->importService->processImport($rows);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $processingTime = round($endTime - $startTime, 2);
            $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);

            Log::info('客戶匯入批次處理完成', [
                'session_id' => $this->sessionId,
                'processing_time' => $processingTime . '秒',
                'memory_used' => $memoryUsed . 'MB',
            ]);

        } catch (\Exception $e) {
            Log::error('客戶匯入處理失敗', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 標記會話為失敗
            if (isset($session)) {
                $session->update([
                    'status' => 'failed',
                    'error_messages' => ['系統錯誤：' . $e->getMessage()],
                    'completed_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    public function chunkSize(): int
    {
        // 使用更小的 chunk 以避免超時
        $memoryUsage = memory_get_usage(true);
        $memoryMB = $memoryUsage / 1024 / 1024;

        if ($memoryMB > 200) {
            return 50; // 記憶體使用高時大幅減少chunk size
        } elseif ($memoryMB > 100) {
            return 100;
        } else {
            return 200; // 較小的預設值
        }
    }

    public static function afterImport(AfterImport $event)
    {
        $importInstance = $event->getConcernable();
        $sessionId = $importInstance->sessionId;

        Log::info('客戶匯入流程完全結束，正在更新最終狀態', ['session_id' => $sessionId]);

        $session = ImportSession::where('session_id', $sessionId)->first();
        if ($session) {
            $errorCount = $session->error_count;
            $successCount = $session->success_count;

            $status = ($errorCount > 0 && $successCount === 0) ? 'failed' : 'completed';

            $session->update([
                'status' => $status,
                'completed_at' => now(),
            ]);

            Log::info('最終狀態已更新', ['session_id' => $sessionId, 'status' => $status]);
        }
    }
}