<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\ImportProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessCustomerImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1小時超時
    public $tries = 3; // 最多重試3次

    protected $batchId;
    protected $filePath;
    private $processedCount = 0;
    private $successCount = 0;
    private $errorCount = 0;
    private $errorMessages = [];

    public function __construct($batchId, $filePath)
    {
        $this->batchId = $batchId;
        $this->filePath = $filePath;
    }

    public function handle()
    {
        try {
            // 更新狀態為處理中
            $this->updateStatus('processing');

            // 讀取 Excel 檔案
            $spreadsheet = IOFactory::load(storage_path('app/' . $this->filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // 移除標題行
            $headerRow = array_shift($rows);
            $headers = array_map(function($header) {
                return trim(strtolower(str_replace(' ', '_', $header)));
            }, $headerRow);

            // 處理每一行資料
            $rowIndex = 2; // 從第2列開始（第1列是標題）
            
            foreach ($rows as $row) {
                // 如果行是空的，跳過
                if (empty(array_filter($row))) {
                    continue;
                }

                // 將行轉換為關聯陣列
                $rowData = array_combine($headers, $row);
                
                $this->processRow($rowData, $rowIndex);
                $rowIndex++;

                // 每處理50筆更新一次進度
                if ($this->processedCount % 50 === 0) {
                    $this->updateProgress();
                }
            }

            // 最終更新進度
            $this->updateProgress(true);

            // 清理檔案
            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            $this->failed($e);
        }
    }

    private function processRow($row, $rowIndex)
    {
        $this->processedCount++;
        
        $idNumber = trim($row['id_number'] ?? '');

        if (!$idNumber) {
            $this->errorCount++;
            $this->errorMessages[] = "第 {$rowIndex} 列：缺少身分證號";
            return;
        }

        $data = $this->processRowData($row, $rowIndex);
        
        if ($data === null) {
            $this->errorCount++;
            return;
        }

        try {
            // 檢查是否已存在
            $existingCustomer = Customer::where('id_number', $idNumber)->first();
            
            if ($existingCustomer) {
                // 更新現有客戶
                $existingCustomer->update($data);
            } else {
                // 建立新客戶
                $data['id_number'] = $idNumber;
                $data['name'] = $data['name'] ?? '未填寫';
                $data['created_by'] = 'import_system';
                Customer::create($data);
            }
            
            $this->successCount++;

        } catch (\Exception $e) {
            $this->errorCount++;
            $msg = "第 {$rowIndex} 列：資料庫錯誤 - " . $e->getMessage();
            
            if (isset($data['phone_number'])) {
                $msg .= ' | phone_number 內容：' . json_encode($data['phone_number']);
            }
            if (isset($data['addresses'])) {
                $msg .= ' | addresses 內容：' . json_encode($data['addresses']);
            }

            $this->errorMessages[] = $msg;
        }
    }

    private function processRowData($row, $rowIndex)
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
                $this->errorMessages[] = "第 {$rowIndex} 列：生日格式錯誤（應為 YYYY-MM-DD）";
                return null;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    private function updateProgress($isCompleted = false)
    {
        $importProgress = ImportProgress::where('batch_id', $this->batchId)->first();
        
        if ($importProgress) {
            $status = $isCompleted ? 'completed' : 'processing';
            
            $importProgress->update([
                'processed_rows' => $this->processedCount,
                'success_count' => $this->successCount,
                'error_count' => $this->errorCount,
                'error_messages' => $this->errorMessages,
                'status' => $status,
                'completed_at' => $isCompleted ? now() : null,
            ]);
        }
    }

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

        // 清理檔案
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }
}