<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ImportProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
class QueuedCustomersImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public $batchId;
    public $timeout = 3600; // 1小時超時
    private $processedCount = 0;
    private $successCount = 0;
    private $errorCount = 0;
    private $errorMessages = [];

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function model(array $row): ?Customer
    {
        // 增加處理計數
        $this->processedCount++;
        
        $idNumber = trim($row['id_number'] ?? '');

        if (!$idNumber) {
            $this->errorCount++;
            $this->errorMessages[] = "第 " . ($this->processedCount + 1) . " 列：缺少身分證號";
            $this->updateProgress();
            return null;
        }

        $data = $this->processRowData($row);
        
        if ($data === null) {
            $this->errorCount++;
            $this->updateProgress();
            return null;
        }

        try {
            // 檢查是否已存在
            $existingCustomer = Customer::where('id_number', $idNumber)->first();
            
            if ($existingCustomer) {
                // 更新現有客戶
                $existingCustomer->update($data);
                $this->successCount++;
                $this->updateProgress();
                return null; // 返回 null 因為是更新而非新建
            } else {
                // 建立新客戶
                $data['id_number'] = $idNumber;
                $data['name'] = $data['name'] ?? '未填寫';
                $this->successCount++;
                $this->updateProgress();
                return new Customer($data);
            }
        } catch (\Exception $e) {
            $this->errorCount++;
            $msg = "第 " . ($this->processedCount + 1) . " 列：資料庫錯誤 - " . $e->getMessage();
            
            if (isset($data['phone_number'])) {
                $msg .= ' | phone_number 內容：' . json_encode($data['phone_number']);
            }
            if (isset($data['addresses'])) {
                $msg .= ' | addresses 內容：' . json_encode($data['addresses']);
            }

            $this->errorMessages[] = $msg;
            $this->updateProgress();
            return null;
        }
    }

    private function processRowData($row)
    {
        $data = [];

        foreach ($row as $key => $value) {
            $value = trim((string) $value);

            if ($value === '' || $key === 'id_number') {
                continue;
            }

            if (in_array($key, ['phone_number', 'addresses'])) {
                // 處理 JSON 或逗號格式
                $array = [];
                if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                    $decoded = json_decode($value, true);
                    $array = is_array($decoded) ? array_filter(array_map('trim', $decoded)) : [];
                } else {
                    $array = array_filter(array_map('trim', explode(',', $value)));
                }

                if (count($array) === 0) {
                    continue;
                }

                $data[$key] = $array;
                continue;
            }

            if ($key === 'birthday' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $this->errorCount++;
                $this->errorMessages[] = "第 " . ($this->processedCount + 1) . " 列：生日格式錯誤（應為 YYYY-MM-DD）";
                return null;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    private function updateProgress()
    {
        // 每處理 50 筆更新一次進度，避免頻繁寫入資料庫
        if ($this->processedCount % 50 === 0 || $this->processedCount === 1) {
            $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();
            
            if ($importProgress) {
                // 檢查是否已完成所有資料
                $isCompleted = $this->processedCount >= $importProgress->total_rows;
                
                $importProgress->update([
                    'processed_rows' => $this->processedCount,
                    'success_count' => $this->successCount,
                    'error_count' => $this->errorCount,
                    'error_messages' => $this->errorMessages,
                    'status' => $isCompleted ? 'completed' : 'processing',
                    'completed_at' => $isCompleted ? now() : null,
                ]);
            }
        }
    }

    public function chunkSize(): int
    {
        return 500; // 每批處理500筆
    }

    public function failed(\Throwable $exception)
    {
        // 當佇列任務失敗時更新狀態
        $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();
        if ($importProgress) {
            $errorMessage = 'Import failed: ' . $exception->getMessage();
            $currentErrors = $importProgress->error_messages ?? [];
            $currentErrors[] = $errorMessage;
            
            $importProgress->update([
                'status' => 'failed',
                'error_messages' => $currentErrors,
                'completed_at' => now(),
            ]);
        }
    }
}