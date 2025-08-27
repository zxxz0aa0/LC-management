<?php

namespace App\Jobs;

use App\Imports\OrdersImport;
use App\Models\ImportProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessOrderImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 14400; // 4小時超時

    public $tries = 5; // 最多重試5次

    public $maxExceptions = 5; // 允許最多5個例外

    public $backoff = [60, 180, 300]; // 重試延遲：1分鐘、3分鐘、5分鐘

    protected $batchId;

    protected $filePath;

    private $processedCount = 0;

    private $successCount = 0;

    private $errorCount = 0;

    private $errorMessages = [];

    private $lastProgressUpdate = 0; // 上次更新進度的時間戳

    public function __construct($batchId, $filePath)
    {
        $this->batchId = $batchId;
        $this->filePath = $filePath;
        $this->lastProgressUpdate = time();
    }

    public function handle()
    {
        Log::info('=== 訂單匯入 Job 開始執行 ===', [
            'batch_id' => $this->batchId,
            'file_path' => $this->filePath,
            'job_id' => $this->job ? $this->job->getJobId() : 'unknown',
            'memory_limit' => ini_get('memory_limit'),
            'time_limit' => ini_get('max_execution_time'),
            'start_time' => now()->toDateTimeString(),
        ]);

        try {
            // 立即更新狀態為處理中，確保前端能看到變化
            $this->updateStatus('processing');
            Log::info('狀態已更新為處理中', ['batch_id' => $this->batchId]);

            // 檢查檔案是否存在
            if (! Storage::exists($this->filePath)) {
                throw new \Exception("匯入檔案不存在：{$this->filePath}");
            }

            Log::info('開始處理訂單匯入檔案', [
                'batch_id' => $this->batchId,
                'file_size' => Storage::size($this->filePath),
            ]);

            // 設定記憶體限制和執行時間
            ini_set('memory_limit', '3G'); // 訂單資料較複雜，需要更多記憶體
            ini_set('max_execution_time', 7200); // 2小時

            // 啟用垃圾回收
            gc_enable();

            // 使用 OrdersImport 處理檔案
            $this->processOrdersImport();

            // 最終更新進度
            $this->updateProgress(true);

            Log::info('=== 訂單匯入任務完成 ===', [
                'batch_id' => $this->batchId,
                'total_processed' => $this->successCount + $this->errorCount,
                'success_count' => $this->successCount,
                'error_count' => $this->errorCount,
                'completion_time' => now()->toDateTimeString(),
                'peak_memory_usage' => memory_get_peak_usage(true) / 1024 / 1024 .' MB',
            ]);

            // 清理檔案
            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            Log::error('訂單匯入任務失敗', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'processed_count' => $this->successCount + $this->errorCount,
                'success_count' => $this->successCount,
                'error_count' => $this->errorCount,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->failed($e);
        }
    }

    /**
     * 處理訂單匯入邏輯
     */
    private function processOrdersImport()
    {
        try {
            // 建立支援進度更新的 OrdersImport 實例
            $importer = new class($this) extends OrdersImport
            {
                private $job;

                public function __construct($job)
                {
                    parent::__construct();
                    $this->job = $job;
                }

                public function collection(\Illuminate\Support\Collection $rows)
                {
                    parent::collection($rows);

                    // 更新Job的統計資訊
                    $this->job->updateCounts($this->successCount, $this->skipCount, $this->errorMessages);
                }

                // 重寫chunkSize方法以適應背景處理
                public function chunkSize(): int
                {
                    return 100; // 背景處理時可以使用更大的分塊
                }
            };

            // 執行匯入
            Excel::import($importer, storage_path('app/'.$this->filePath));

            Log::info('訂單匯入處理完成', [
                'batch_id' => $this->batchId,
                'success_count' => $this->successCount,
                'error_count' => $this->errorCount,
            ]);

        } catch (\Exception $e) {
            Log::error('OrdersImport 執行失敗', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 更新統計資訊（由OrdersImport呼叫）
     */
    public function updateCounts($successCount, $errorCount, $errorMessages)
    {
        $this->successCount = $successCount;
        $this->errorCount = $errorCount;
        $this->errorMessages = $errorMessages;

        // 智能進度更新：定期更新進度
        if ($this->shouldUpdateProgress()) {
            $this->updateProgress();
            $this->lastProgressUpdate = time();
        }
    }

    /**
     * 判斷是否應該更新進度
     */
    private function shouldUpdateProgress()
    {
        $currentTime = time();
        $timeSinceLastUpdate = $currentTime - $this->lastProgressUpdate;

        // 每30秒更新一次進度
        return $timeSinceLastUpdate >= 30;
    }

    /**
     * 更新進度到資料庫
     */
    private function updateProgress($isCompleted = false)
    {
        $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();

        if ($importProgress) {
            $status = $isCompleted ? 'completed' : 'processing';
            $totalProcessed = $this->successCount + $this->errorCount;

            $importProgress->update([
                'processed_rows' => $totalProcessed,
                'success_count' => $this->successCount,
                'error_count' => $this->errorCount,
                'error_messages' => $this->errorMessages,
                'status' => $status,
                'completed_at' => $isCompleted ? now() : null,
                'updated_at' => now(),
            ]);

            Log::debug('進度更新', [
                'batch_id' => $this->batchId,
                'processed' => $totalProcessed,
                'success' => $this->successCount,
                'errors' => $this->errorCount,
                'status' => $status,
            ]);
        }
    }

    /**
     * 更新狀態
     */
    private function updateStatus($status)
    {
        $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();

        if ($importProgress) {
            $importProgress->update([
                'status' => $status,
                'started_at' => $status === 'processing' ? now() : $importProgress->started_at,
            ]);
        }
    }

    /**
     * 任務失敗處理
     */
    public function failed(\Throwable $exception)
    {
        // 當佇列任務失敗時更新狀態
        $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();

        if ($importProgress) {
            $errorMessage = '訂單匯入失敗: '.$exception->getMessage();
            $currentErrors = $importProgress->error_messages ?? [];
            $currentErrors[] = $errorMessage;

            $importProgress->update([
                'status' => 'failed',
                'error_messages' => $currentErrors,
                'completed_at' => now(),
            ]);
        }

        Log::error('訂單匯入Job失敗', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // 清理檔案
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }

    /**
     * 心跳機制，定期更新處理狀態以防止超時
     */
    private function heartbeat()
    {
        $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();

        if ($importProgress) {
            $importProgress->update([
                'updated_at' => now(), // 更新最後活動時間
            ]);
        }

        Log::debug('訂單匯入心跳更新', [
            'batch_id' => $this->batchId,
            'processed_rows' => $this->successCount + $this->errorCount,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 .' MB',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
