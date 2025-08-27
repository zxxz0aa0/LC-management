<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustomerImportService
{
    private $importSession;
    private $processedCount = 0;
    private $successCount = 0;
    private $errorCount = 0;
    private $errorMessages = [];
    /**
     * 中英文標題對照表
     */
    private static $headerMapping = [
        '姓名' => 'name',
        '身分證號' => 'id_number', 
        '出生年月日' => 'birthday',
        '性別' => 'gender',
        '聯絡電話' => 'phone_number',
        '地址' => 'addresses',
        '聯絡人' => 'contact_person',
        '聯絡人電話' => 'contact_phone',
        '關係' => 'contact_relationship',
        '電子郵件' => 'email',
        '輪椅' => 'wheelchair',
        '爬梯機' => 'stair_climbing_machine',
        '共乘' => 'ride_sharing',
        '身分別' => 'identity',
        '備註' => 'note',
        'a單位' => 'a_mechanism',
        '個管師' => 'a_manager',
        '特殊情況' => 'special_status',
        '縣市照顧' => 'county_care',
        '服務公司' => 'service_company',
        '照會日期' => 'referral_date',
        '狀態' => 'status',
    ];

    public function __construct()
    {
        
    }

    /**
     * 設定匯入會話
     */
    public function setImportSession(ImportSession $session)
    {
        $this->importSession = $session;
        return $this;
    }


    /**
     * 處理匯入資料
     */
    public function processImport(Collection $rows): array
    {
        Log::info('開始客戶匯入處理', [
            'session_id' => $this->importSession?->id,
            'total_rows' => $rows->count(),
        ]);

        if ($rows->isEmpty()) {
            return $this->getImportResult();
        }

        // 取得第一列並檢查是否為標題列
        $firstRow = $rows->first()->toArray();
        
        // 檢查第一列是否包含中文標題欄位
        $hasHeaders = false;
        $headerMatchCount = 0;
        
        foreach ($firstRow as $cell) {
            $trimmedCell = trim((string)$cell);
            if (isset(self::$headerMapping[$trimmedCell])) {
                $headerMatchCount++;
            }
        }
        
        // 如果匹配到3個以上的標題，視為有標題列
        $hasHeaders = $headerMatchCount >= 3;
        
        if ($hasHeaders) {
            Log::info('檢測到標題列，使用動態標題對照');
            $headers = $firstRow;
            $dataRows = $rows->skip(1);
        } else {
            Log::info('未檢測到標題列，使用預設欄位順序', [
                'first_row_sample' => array_slice($firstRow, 0, 5),
                'header_match_count' => $headerMatchCount,
            ]);
            
            // 預設的欄位順序 - 根據你的 Excel 檔案結構調整
            $headers = [
                '姓名',           // 0
                '身分證號',       // 1 
                '出生年月日',     // 2
                '性別',           // 3
                '聯絡電話',       // 4
                '地址',           // 5
                '聯絡人',         // 6
                '聯絡人電話',     // 7
                '關係',           // 8
                '電子郵件',       // 9
                '輪椅',           // 10
                '爬梯機',         // 11
                '共乘',           // 12
                '身分別',         // 13
                '備註',           // 14
                'a單位',          // 15
                '個管師',         // 16
                '特殊情況',       // 17
                '縣市照顧',       // 18
                '服務公司',       // 19
                '照會日期',       // 20
                '狀態',           // 21
            ];
            
            // 確保標題陣列長度與資料欄位數相符
            $headerCount = count($headers);
            $dataCount = count($firstRow);
            
            if ($dataCount > $headerCount) {
                // 如果資料欄位比預設標題多，補充未知欄位
                for ($i = $headerCount; $i < $dataCount; $i++) {
                    $headers[$i] = "未知欄位_{$i}";
                }
            }
            
            $dataRows = $rows; // 所有列都是資料列
        }

        Log::info('匯入標題檢查', [
            'has_headers' => $hasHeaders,
            'headers' => $headers,
            'data_rows_count' => $dataRows->count(),
            'header_match_count' => $headerMatchCount,
        ]);

        // 處理資料列
        $this->processDataRows($dataRows, $headers);

        // 返回處理結果
        return $this->getImportResult();
    }

    /**
     * 處理資料列
     */
    private function processDataRows(Collection $dataRows, array $headers)
    {
        $batchSize = 50; // 每50筆提交一次（降低記憶體使用）
        $currentBatch = [];

        foreach ($dataRows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                Log::debug('跳過空白列', ['row_index' => $index + 2]);
                continue;
            }

            $result = $this->processCustomerRow($row->toArray(), $headers, $index + 2);
            
            if ($result['success']) {
                $currentBatch[] = $result['data'];
                $this->successCount++;
            } else {
                $this->errorCount++;
                $this->errorMessages[] = $result['error'];
            }

            $this->processedCount++;

            // 批次處理資料庫操作
            if (count($currentBatch) >= $batchSize) {
                $this->processBatch($currentBatch);
                $currentBatch = [];

                // 更新進度
                $this->updateProgress();
                
                // 定期執行垃圾回收
                if ($this->processedCount % 500 === 0) {
                    gc_collect_cycles();
                }
            }
        }

        // 處理剩餘的資料
        if (!empty($currentBatch)) {
            $this->processBatch($currentBatch);
            $this->updateProgress();
        }
    }

    /**
     * 處理單筆客戶資料
     */
    private function processCustomerRow(array $row, array $headers, int $rowNumber): array
    {
        try {
            // 映射標題
            $mappedData = $this->mapHeaders($row, $headers);

            // 驗證必填欄位
            $validation = $this->validateCustomerData($mappedData, $rowNumber);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }

            // 處理特殊欄位
            $customerData = $this->prepareCustomerData($mappedData);

            return [
                'success' => true,
                'data' => $customerData
            ];

        } catch (\Exception $e) {
            Log::error('處理客戶資料失敗', [
                'row_number' => $rowNumber,
                'error' => $e->getMessage(),
                'row_data' => $row,
            ]);

            return [
                'success' => false,
                'error' => "第 {$rowNumber} 列處理失敗：{$e->getMessage()}"
            ];
        }
    }

    /**
     * 映射中英文標題
     */
    private function mapHeaders(array $row, array $headers): array
    {
        $mappedData = [];
        $englishHeaders = array_values(self::$headerMapping);

        foreach ($row as $index => $value) {
            if (!isset($headers[$index])) continue;

            $header = trim($headers[$index]);
            
            // 檢查是否為中文標題
            if (isset(self::$headerMapping[$header])) {
                $englishKey = self::$headerMapping[$header];
                $mappedData[$englishKey] = $value;
            }
            // 檢查是否已經是英文標題
            elseif (in_array($header, $englishHeaders)) {
                $mappedData[$header] = $value;
            }
            // 保留未知標題
            else {
                $mappedData[$header] = $value;
                Log::warning('未知標題', ['header' => $header]);
            }
        }

        return $mappedData;
    }

    /**
     * 驗證客戶資料
     */
    private function validateCustomerData(array $data, int $rowNumber): array
    {
        // 必填欄位驗證
        if (empty(trim($data['name'] ?? ''))) {
            Log::warning('姓名欄位驗證失敗', [
                'row_number' => $rowNumber,
                'data_keys' => array_keys($data),
                'name_value' => $data['name'] ?? 'NOT_SET',
                'all_data' => $data,
            ]);
            
            return [
                'valid' => false,
                'error' => "第 {$rowNumber} 列：缺少姓名 (可用欄位: " . implode(', ', array_keys($data)) . ")"
            ];
        }

        if (empty(trim($data['id_number'] ?? ''))) {
            return [
                'valid' => false,
                'error' => "第 {$rowNumber} 列：缺少身分證號"
            ];
        }

        // EMAIL格式驗證（如果有值的話）
        if (!empty($data['email'])) {
            $validator = Validator::make(['email' => $data['email']], [
                'email' => 'email'
            ]);

            if ($validator->fails()) {
                return [
                    'valid' => false,
                    'error' => "第 {$rowNumber} 列：電子郵件格式錯誤"
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * 準備客戶資料
     */
    private function prepareCustomerData(array $data): array
    {
        return [
            'name' => trim($data['name'] ?? ''),
            'id_number' => trim($data['id_number'] ?? ''),
            'birthday' => $this->parseDate($data['birthday'] ?? null),
            'gender' => $data['gender'] ?? null,
            'phone_number' => $this->parseMultipleValues($data['phone_number'] ?? ''),
            'addresses' => $this->parseMultipleValues($data['addresses'] ?? ''),
            'contact_person' => $data['contact_person'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_relationship' => $data['contact_relationship'] ?? null,
            'email' => $this->parseEmail($data['email'] ?? null),
            'wheelchair' => $data['wheelchair'] ?? '否',
            'stair_climbing_machine' => $data['stair_climbing_machine'] ?? '否',
            'ride_sharing' => $data['ride_sharing'] ?? '是',
            'identity' => $data['identity'] ?? null,
            'note' => $data['note'] ?? null,
            'a_mechanism' => $data['a_mechanism'] ?? null,
            'a_manager' => $data['a_manager'] ?? null,
            'special_status' => $data['special_status'] ?? '一般',
            'county_care' => $data['county_care'] ?? null,
            'service_company' => $data['service_company'] ?? null,
            'referral_date' => $this->parseDate($data['referral_date'] ?? null),
            'status' => $data['status'] ?? '開案中',
            'created_by' => auth()->user()?->name ?? 'import_system',
            'updated_by' => auth()->user()?->name ?? 'import_system',
        ];
    }

    /**
     * 處理EMAIL欄位（移除重複檢查約束）
     */
    private function parseEmail($email): ?string
    {
        if (empty($email) || $email === '0' || strtolower($email) === 'null') {
            return null;
        }
        
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) ? trim($email) : null;
    }

    /**
     * 解析多值欄位（電話、地址）
     */
    private function parseMultipleValues($value): array
    {
        if (empty($value)) {
            return [];
        }

        // 已經是JSON格式
        if (is_string($value) && str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_filter(array_map('trim', $decoded)) : [];
        }

        // 逗號分隔
        return array_filter(array_map('trim', explode(',', (string)$value)));
    }

    /**
     * 解析日期
     */
    private function parseDate($date): ?string
    {
        if (empty($date) || $date === '#VALUE!') {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 檢查是否為空白列
     */
    private function isRowEmpty($row): bool
    {
        if ($row->isEmpty()) {
            return true;
        }

        return $row->every(function ($value) {
            return empty(trim((string)$value));
        });
    }

    /**
     * 批次處理資料庫操作
     */
    private function processBatch(array $batch)
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch as $customerData) {
                try {
                    Customer::updateOrCreate(
                        ['id_number' => $customerData['id_number']],
                        $customerData
                    );
                } catch (\Exception $e) {
                    Log::error('批次處理客戶資料失敗', [
                        'id_number' => $customerData['id_number'],
                        'error' => $e->getMessage()
                    ]);
                    
                    $this->errorCount++;
                    $this->successCount--;
                    $this->errorMessages[] = "身分證號 {$customerData['id_number']} 儲存失敗：{$e->getMessage()}";
                }
            }
        });
    }

    /**
     * 更新匯入進度
     */
    private function updateProgress()
    {
        if ($this->importSession) {
            $this->importSession->update([
                'processed_rows' => $this->processedCount,
                'success_count' => $this->successCount,
                'error_count' => $this->errorCount,
                'error_messages' => $this->errorMessages,
                'status' => 'processing',
            ]);
        }
    }

    

    /**
     * 取得匯入結果
     */
    private function getImportResult(): array
    {
        return [
            'processed_count' => $this->processedCount,
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'error_messages' => $this->errorMessages,
        ];
    }
}