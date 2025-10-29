<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Services\DateTimeParser;
use App\Services\ExcelFieldMapper;
use App\Services\TaiwanAddressResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class OrdersImport implements ToCollection, WithChunkReading
{
    public $successCount = 0;

    public $skipCount = 0;

    public $errorMessages = [];

    private $headingRow = [];

    private $dateTimeParser;

    private $addressResolver;

    private $fieldMapper;

    private $customerCache = []; // 客戶查找快取

    public function __construct()
    {
        $this->dateTimeParser = new DateTimeParser;
        $this->addressResolver = new TaiwanAddressResolver;
        $this->fieldMapper = new ExcelFieldMapper;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // 記錄開始處理時間和記憶體使用
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        Log::channel('import')->info('開始處理訂單匯入批次', [
            'rows_count' => $rows->count(),
            'start_memory' => round($startMemory / 1024 / 1024, 2).'MB',
            'start_time' => date('Y-m-d H:i:s'),
        ]);

        try {
            // 檢測第一行是否為有效的標題行
            $firstRow = $rows->first()->toArray();
            $isValidHeader = $this->fieldMapper->isValidHeaderRow($firstRow);

            if ($isValidHeader) {
                // 第一行是標題行
                $this->headingRow = $firstRow;
                $dataRows = $rows->skip(1);
                $rowIndex = 2; // 從第2列開始讀資料（第1列為標題）

                Log::channel('import')->info('檢測到標題行格式', [
                    'headers' => $this->headingRow,
                    'headers_count' => count($this->headingRow),
                    'data_start_row' => 2,
                ]);
            } else {
                // 第一行是資料行，使用預設欄位映射
                $this->headingRow = $this->fieldMapper->getDefaultFieldMapping();
                $dataRows = $rows; // 從第一行開始處理資料
                $rowIndex = 1;

                Log::channel('import')->info('檢測到無標題格式，使用預設映射', [
                    'default_mapping' => $this->headingRow,
                    'data_start_row' => 1,
                    'first_row_data' => $firstRow,
                ]);
            }

            // 檢測匯入格式
            $isSimpleFormat = $this->fieldMapper->detectSimpleFormat($this->headingRow);

            Log::channel('import')->info('匯入格式檢測', [
                'format' => $isSimpleFormat ? '簡化格式' : '完整格式',
                'row_count' => $dataRows->count(),
            ]);

            $processedInBatch = 0;

            foreach ($dataRows as $row) {
                try {
                    // 檢查資料是否有效
                    if ($this->isRowEmpty($row)) {
                        $this->skipCount++;
                        $rowIndex++;

                        continue;
                    }

                    // 定期進行記憶體檢查和清理
                    if ($processedInBatch > 0 && $processedInBatch % 50 === 0) {
                        $this->performMemoryCleanup($processedInBatch);
                    }

                    // 根據格式處理訂單
                    if ($isSimpleFormat) {
                        $success = $this->processSimpleFormatOrder($row, $rowIndex);
                    } else {
                        $success = $this->processFullFormatOrder($row, $rowIndex);
                    }

                    if ($success) {
                        $this->successCount++;
                    } else {
                        $this->skipCount++;
                    }

                    $processedInBatch++;

                } catch (\Exception $e) {
                    $this->skipCount++;
                    $this->handleRowError($e, $rowIndex, $row);
                } catch (\Throwable $e) {
                    // 捕獲更嚴重的錯誤，如記憶體不足
                    $this->skipCount++;
                    $this->handleCriticalError($e, $rowIndex);
                }

                $rowIndex++;
            }

            // 最終清理
            $this->performFinalCleanup();

        } catch (\Exception $e) {
            Log::channel('import')->error('訂單匯入批次處理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processed_count' => $this->successCount,
                'error_count' => $this->skipCount,
            ]);
            throw $e;
        }

        // 記錄最終統計
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        Log::channel('import')->info('匯入批次完成統計', [
            'success_count' => $this->successCount,
            'skip_count' => $this->skipCount,
            'error_count' => count($this->errorMessages),
            'total_processed' => ($rowIndex - 2),
            'execution_time' => round($endTime - $startTime, 2).'s',
            'memory_used' => round(($endMemory - $startMemory) / 1024 / 1024, 2).'MB',
            'peak_memory' => round($peakMemory / 1024 / 1024, 2).'MB',
        ]);
    }

    /**
     * 處理簡化格式訂單
     */
    private function processSimpleFormatOrder($row, $rowIndex)
    {
        // 提取客戶資料
        $customerData = $this->extractCustomerData($row, 'simple');

        // 驗證必填欄位（對於無標題檔案，使用位置驗證）
        $errors = $this->fieldMapper->validateRequiredFields($row, $this->headingRow, $rowIndex);
        if (! empty($errors)) {
            $this->errorMessages = array_merge($this->errorMessages, $errors);

            return false;
        }

        // 查找或創建客戶
        $customer = $this->findExistingCustomer($customerData['name'], $customerData['phone']);

        // 建立基礎訂單資料
        $baseOrderData = $this->buildBaseOrderData($row, $customerData, $customer);

        // 處理簡化格式特有的地址邏輯
        $addressData = $this->processSimpleAddressFields($row);

        $orderData = array_merge($baseOrderData, $addressData);

        return $this->createOrder($orderData);
    }

    /**
     * 處理完整格式訂單
     */
    private function processFullFormatOrder($row, $rowIndex)
    {
        // 提取客戶資料
        $customerData = $this->extractCustomerData($row, 'full');

        // 驗證必填欄位
        $errors = $this->fieldMapper->validateRequiredFields($row, $this->headingRow, $rowIndex);
        if (! empty($errors)) {
            $this->errorMessages = array_merge($this->errorMessages, $errors);

            return false;
        }

        // 查找或創建客戶
        $customer = $this->findExistingCustomer($customerData['name'], $customerData['phone']);

        // 建立基礎訂單資料
        $baseOrderData = $this->buildBaseOrderData($row, $customerData, $customer);

        // 處理完整格式特有的地址邏輯
        $addressData = $this->processFullAddressFields($row);

        $orderData = array_merge($baseOrderData, $addressData);

        return $this->createOrder($orderData);
    }

    /**
     * 處理簡化格式地址欄位
     */
    private function processSimpleAddressFields($row)
    {
        // 檢查是否為無標題檔案（使用預設映射）
        $usePositionMapping = is_array($this->headingRow) &&
                             isset($this->headingRow[0]) &&
                             in_array($this->headingRow[0], ['order_code', 'order_number']);

        if ($usePositionMapping) {
            // 使用位置映射讀取地址資訊（完整格式28欄）
            $pickupArea = $this->fieldMapper->getValueByPosition($row, 9); // 上車區域
            $dropoffArea = $this->fieldMapper->getValueByPosition($row, 12); // 下車區域
            $pickupAddress = $this->fieldMapper->getValueByPosition($row, 10); // 上車地址
            $dropoffAddress = $this->fieldMapper->getValueByPosition($row, 13); // 下車地址
        } else {
            // 使用標題映射讀取地址資訊
            $pickupArea = $this->fieldMapper->getRowValue($row, ['pickup_district', 'origin_area', 'pickup_area', '上車區域', '上車區', 'origin_district'], $this->headingRow);
            $dropoffArea = $this->fieldMapper->getRowValue($row, ['dropoff_district', 'dest_area', 'dropoff_area', '下車區域', '下車區', 'dest_district'], $this->headingRow);
            $pickupAddress = $this->fieldMapper->getRowValue($row, ['pickup_address', 'origin_address', '上車地址', '起點地址', 'pickup'], $this->headingRow);
            $dropoffAddress = $this->fieldMapper->getRowValue($row, ['dropoff_address', 'destination_address', '下車地址', '終點地址', 'dropoff', 'destination', 'dest'], $this->headingRow);
        }

        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('簡化格式地址處理', [
                'pickup_area' => $pickupArea,
                'dropoff_area' => $dropoffArea,
                'pickup_address' => $pickupAddress,
                'dropoff_address' => $dropoffAddress,
            ]);
        }

        // 推斷縣市並組合完整地址
        $pickupCounty = $this->addressResolver->inferCountyFromArea($pickupArea);
        $dropoffCounty = $this->addressResolver->inferCountyFromArea($dropoffArea);

        $pickupFullAddress = $pickupAddress;
        $dropoffFullAddress = $dropoffAddress;

        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('簡化格式最終地址欄位', [
                'pickup_county' => $pickupCounty,
                'pickup_district' => $pickupArea,
                'pickup_address' => $pickupFullAddress,
                'dropoff_county' => $dropoffCounty,
                'dropoff_district' => $dropoffArea,
                'dropoff_address' => $dropoffFullAddress,
            ]);
        }

        return [
            'pickup_county' => $pickupCounty,
            'pickup_district' => $pickupArea,
            'pickup_address' => $pickupFullAddress,
            'dropoff_county' => $dropoffCounty,
            'dropoff_district' => $dropoffArea,
            'dropoff_address' => $dropoffFullAddress,
        ];
    }

    /**
     * 處理完整格式地址欄位
     */
    private function processFullAddressFields($row)
    {
        $pickupAddress = $this->fieldMapper->getRowValue($row, ['pickup_address', 'origin_address', '上車地址', '起點地址'], $this->headingRow);
        $dropoffAddress = $this->fieldMapper->getRowValue($row, ['dropoff_address', 'destination_address', '下車地址', '終點地址'], $this->headingRow);

        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('完整格式地址處理', [
                'pickup_address' => $pickupAddress,
                'dropoff_address' => $dropoffAddress,
            ]);
        }

        // 從完整地址提取縣市和區域
        $pickupCounty = $this->addressResolver->extractCounty($pickupAddress);
        $pickupDistrict = $this->addressResolver->extractDistrict($pickupAddress);
        $dropoffCounty = $this->addressResolver->extractCounty($dropoffAddress);
        $dropoffDistrict = $this->addressResolver->extractDistrict($dropoffAddress);

        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('完整格式最終地址欄位', [
                'pickup_county' => $pickupCounty,
                'pickup_district' => $pickupDistrict,
                'pickup_address' => $pickupAddress,
                'dropoff_county' => $dropoffCounty,
                'dropoff_district' => $dropoffDistrict,
                'dropoff_address' => $dropoffAddress,
            ]);
        }

        return [
            'pickup_county' => $pickupCounty,
            'pickup_district' => $pickupDistrict,
            'pickup_address' => $pickupAddress,
            'dropoff_county' => $dropoffCounty,
            'dropoff_district' => $dropoffDistrict,
            'dropoff_address' => $dropoffAddress,
        ];
    }

    /**
     * 提取客戶資料
     */
    private function extractCustomerData($row, $format = 'full')
    {
        // 檢查是否使用位置映射
        $usePositionMapping = is_array($this->headingRow) &&
                             isset($this->headingRow[0]) &&
                             in_array($this->headingRow[0], ['order_code', 'order_number']);

        if ($usePositionMapping) {
            // 使用位置映射
            return [
                'name' => $this->fieldMapper->getValueByPosition($row, 1), // 姓名
                'phone' => $this->fieldMapper->getValueByPosition($row, 2), // 電話
                'customer_id_number' => $this->fieldMapper->getValueByPosition($row, 3), // 編號/身分證
                'identity' => null, // 簡化格式通常沒有身份別
            ];
        } else {
            // 使用標題映射
            $nameKeys = $format === 'simple'
                ? ['name', '姓名', 'customer_name']
                : ['customer_name', 'name', '客戶姓名', '姓名'];

            $customerIdNumber = $this->fieldMapper->getRowValue($row, ['customer_id_number', '身分證', '編號', 'unit_number'], $this->headingRow);

            return [
                'name' => $this->fieldMapper->getRowValue($row, $nameKeys, $this->headingRow),
                'phone' => $this->fieldMapper->getRowValue($row, ['customer_phone', 'phone', 'tel', '客戶電話', '電話', '聯絡'], $this->headingRow),
                'customer_id_number' => $customerIdNumber,
                'identity' => $this->fieldMapper->getRowValue($row, ['identity', '身分別'], $this->headingRow),
            ];
        }
    }

    /**
     * 建立基礎訂單資料
     */
    private function buildBaseOrderData($row, $customerData, $customer)
    {
        // 檢查是否使用位置映射
        $usePositionMapping = is_array($this->headingRow) &&
                             isset($this->headingRow[0]) &&
                             in_array($this->headingRow[0], ['order_code', 'order_number']);

        if ($usePositionMapping) {
            // 使用位置映射（根據實際資料格式調整）
            return [
                // 基本訂單資訊
                'order_number' => $this->fieldMapper->getValueByPosition($row, 0), // 訂單編號
                'order_type' => $this->fieldMapper->getValueByPosition($row, 4), // 類型
                'service_company' => $this->fieldMapper->getValueByPosition($row, 5), // 服務公司

                // 日期時間
                'ride_date' => $this->fieldMapper->getValueByPosition($row, 6), // 日期
                'ride_time' => $this->dateTimeParser->parseTime($this->fieldMapper->getValueByPosition($row, 7)), // 時間（自動轉換小數）

                // 客戶快照資訊
                'customer_id' => $customer ? $customer->id : null,
                'customer_name' => $customerData['name'],
                'customer_phone' => $customerData['phone'],
                'customer_id_number' => $customerData['customer_id_number'],
                'identity' => $customerData['identity'],

                // 特殊需求（從對應位置讀取）
                'wheelchair' => $this->fieldMapper->getValueByPosition($row, 14) ?: '未知',
                'stair_machine' => $this->fieldMapper->getValueByPosition($row, 15) ?: '未知',
                'companions' => $this->fieldMapper->getValueByPosition($row, 16) ?: 0,

                // 共乘資訊（簡化格式通常沒有）
                'carpool_name' => null,
                'carpool_id' => null,

                // 駕駛資訊
                'driver_name' => $this->fieldMapper->getValueByPosition($row, 19), // 駕駛姓名
                'driver_fleet_number' => $this->fieldMapper->getValueByPosition($row, 20), // 駕駛隊編
                'driver_plate_number' => $this->fieldMapper->getValueByPosition($row, 21), // 車牌號碼
                'status' => $this->determineOrderStatus($row),

                // 其他資訊
                'special_status' => $this->fieldMapper->getValueByPosition($row, 23), // 特殊狀態
                'remark' => $this->fieldMapper->getValueByPosition($row, 24), // 備註
                'created_by' => auth()->user()->name ?? 'System',
            ];
        } else {
            // 使用標題映射
            return [
                // 基本訂單資訊
                'order_number' => $this->fieldMapper->getRowValue($row, ['order_number', 'order_code', '訂單編號', '單號'], $this->headingRow),
                'order_type' => $this->fieldMapper->getRowValue($row, ['order_type', '訂單類型', 'type', '類型'], $this->headingRow),
                'service_company' => $this->fieldMapper->getRowValue($row, ['service_company', '服務公司', 'company', '公司'], $this->headingRow),

                // 日期時間 (增加調試日誌)
                'ride_date' => $this->parseAndLogDate($row),
                'ride_time' => $this->parseAndLogTime($row),

                // 客戶快照資訊
                'customer_id' => $customer ? $customer->id : null,
                'customer_name' => $customerData['name'],
                'customer_phone' => $customerData['phone'],
                'customer_id_number' => $customerData['customer_id_number'],
                'identity' => $customerData['identity'],

                // 特殊需求
                'wheelchair' => $this->fieldMapper->getRowValue($row, ['wheelchair', '輪椅', '輪椅需求'], $this->headingRow),
                'stair_machine' => $this->fieldMapper->getRowValue($row, ['stair_machine', '爬梯機', '爬梯機需求'], $this->headingRow),
                'companions' => $this->fieldMapper->getRowValue($row, ['companions', '陪同人數', '陪伴者'], $this->headingRow),

                // 共乘資訊
                'carpool_name' => $this->fieldMapper->getRowValue($row, ['carpool_name', '共乘姓名', '共乘者姓名'], $this->headingRow),
                'carpool_id' => $this->fieldMapper->getRowValue($row, ['carpool_id', '共乘身分證', '共乘者身分證'], $this->headingRow),

                // 駕駛資訊
                'driver_name' => $this->fieldMapper->getRowValue($row, ['driver_name', '駕駛姓名', '司機姓名'], $this->headingRow),
                'driver_fleet_number' => $this->fieldMapper->getRowValue($row, ['driver_fleet_number', '隊員編號', 'assigned_user_id', '駕駛隊編'], $this->headingRow),
                'driver_plate_number' => $this->fieldMapper->getRowValue($row, ['driver_plate_number', '車牌號碼', '車牌'], $this->headingRow),
                'status' => $this->determineOrderStatus($row),

                // 其他資訊
                'special_status' => $this->fieldMapper->getRowValue($row, ['special_status', '特殊狀態', '特殊標記'], $this->headingRow),
                'remark' => $this->fieldMapper->getRowValue($row, ['remark', '備註', '註記', '說明'], $this->headingRow),
                'created_by' => auth()->user()->name ?? 'System',
            ];
        }
    }

    /**
     * 創建訂單（優化版）
     */
    private function createOrder($orderData)
    {
        try {
            // 檢查必要欄位
            if (empty($orderData['order_number'])) {
                $this->errorMessages[] = '訂單編號不能為空';

                return false;
            }

            // 檢查重複訂單（使用存在性檢查，效能更好）
            if (Order::where('order_number', $orderData['order_number'])->exists()) {
                $this->errorMessages[] = "訂單編號 {$orderData['order_number']} 已存在，跳過匯入";

                return false;
            }

            // 資料清理和驗證
            $cleanOrderData = $this->cleanOrderData($orderData);

            if ($cleanOrderData === false) {
                return false; // 資料清理失敗，錯誤訊息已在cleanOrderData中添加
            }

            // 建立訂單
            Order::create($cleanOrderData);

            Log::channel('import')->debug('訂單建立成功', [
                'order_number' => $cleanOrderData['order_number'],
                'customer_name' => $cleanOrderData['customer_name'] ?? 'unknown',
            ]);

            return true;

        } catch (\Illuminate\Database\QueryException $e) {
            // 處理資料庫相關錯誤
            $detailedError = $this->simplifyDbError($e->getMessage());
            $this->errorMessages[] = "訂單 {$orderData['order_number']} 資料庫錯誤：{$detailedError}";

            // 記錄詳細的資料庫錯誤訊息
            Log::channel('import')->error('訂單創建資料庫錯誤', [
                'order_number' => $orderData['order_number'] ?? 'unknown',
                'detailed_error' => $detailedError,
                'raw_error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'sql' => $e->getSql() ?? 'unknown',
                'bindings' => config('app.debug') ? $e->getBindings() : null,
                'order_data' => config('app.import_debug_log', false) ? $orderData : null,
            ]);

            return false;

        } catch (\Exception $e) {
            $this->errorMessages[] = "訂單 {$orderData['order_number']} 創建失敗：".$e->getMessage();

            Log::channel('import')->error('訂單創建失敗', [
                'order_number' => $orderData['order_number'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return false;
        }
    }

    /**
     * 清理和驗證訂單資料
     */
    private function cleanOrderData($orderData)
    {
        try {
            // 移除空值和不必要的欄位
            $cleanData = array_filter($orderData, function ($value, $key) {
                return $value !== null && $value !== '' && ! in_array($key, ['carpoolSearchInput']);
            }, ARRAY_FILTER_USE_BOTH);

            // 執行詳細的欄位驗證
            $validationErrors = $this->validateOrderFields($cleanData, $orderData['order_number']);
            if (! empty($validationErrors)) {
                $this->errorMessages = array_merge($this->errorMessages, $validationErrors);

                return false;
            }

            // 設置預設值和資料清理
            $cleanData = $this->applyDefaultValues($cleanData);

            return $cleanData;

        } catch (\Exception $e) {
            $this->errorMessages[] = "訂單 {$orderData['order_number']} 資料清理失敗：".$e->getMessage();

            return false;
        }
    }

    /**
     * 驗證所有訂單欄位
     */
    private function validateOrderFields($data, $orderNumber)
    {
        $errors = [];

        // 1. 驗證日期格式
        if (isset($data['ride_date'])) {
            if (! $this->isValidDate($data['ride_date'])) {
                $errors[] = "訂單 {$orderNumber} → 用車日期: 格式錯誤 (目前值: {$data['ride_date']}) - 請使用 YYYY-MM-DD 格式，如: 2025-08-27";
            }
        }

        // 2. 驗證時間格式
        if (isset($data['ride_time'])) {
            if (config('app.import_debug_log', false)) {
                Log::channel('import')->debug('開始驗證訂單時間格式', [
                    'order_number' => $orderNumber,
                    'ride_time' => $data['ride_time'],
                    'ride_time_type' => gettype($data['ride_time']),
                ]);
            }

            if (! $this->isValidTime($data['ride_time'])) {
                $errors[] = "訂單 {$orderNumber} → 用車時間: 格式錯誤 (目前值: {$data['ride_time']}) - 請使用 HH:MM 或 HH:MM:SS 格式，如: 08:30, 08:30:00";
            }
        }

        // 3. 驗證訂單狀態
        if (isset($data['status'])) {
            $validStatuses = ['open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD'];
            $validChineseStatuses = ['待派遣', '已指派', '已候補', '黑名單', '已取消', '一般取消', '別家有車', '!取消', 'X取消'];

            // 先嘗試轉換狀態
            $convertedStatus = $this->fieldMapper->convertOrderStatus($data['status']);

            if (! in_array($convertedStatus, $validStatuses)) {
                $allowedValues = implode(', ', $validChineseStatuses).' 或 '.implode(', ', $validStatuses);
                $errors[] = "訂單 {$orderNumber} → 訂單狀態: 無效值 (目前值: {$data['status']}) - 允許的值: {$allowedValues}";
            } else {
                // 更新為轉換後的英文值
                $data['status'] = $convertedStatus;
            }
        }

        // 4. 驗證輪椅需求
        if (isset($data['wheelchair'])) {
            $validValues = ['是', '否', '未知'];
            if (! in_array($data['wheelchair'], $validValues)) {
                $errors[] = "訂單 {$orderNumber} → 輪椅需求: 無效值 (目前值: {$data['wheelchair']}) - 允許的值: ".implode(', ', $validValues);
            }
        }

        // 5. 驗證爬梯機需求
        if (isset($data['stair_machine'])) {
            $validValues = ['是', '否', '未知'];
            if (! in_array($data['stair_machine'], $validValues)) {
                $errors[] = "訂單 {$orderNumber} → 爬梯機需求: 無效值 (目前值: {$data['stair_machine']}) - 允許的值: ".implode(', ', $validValues);
            }
        }

        // 6. 驗證陪同人數
        if (isset($data['companions'])) {
            if (! is_numeric($data['companions']) || $data['companions'] < 0 || $data['companions'] > 9) {
                $errors[] = "訂單 {$orderNumber} → 陪同人數: 數值錯誤 (目前值: {$data['companions']}) - 必須是 0-9 之間的數字";
            }
        }

        // 7. 驗證字串長度限制
        $stringFields = [
            'order_number' => ['label' => '訂單編號', 'max' => 255],
            'customer_name' => ['label' => '客戶姓名', 'max' => 255],
            'customer_phone' => ['label' => '客戶電話', 'max' => 255],
            'customer_id_number' => ['label' => '客戶身分證', 'max' => 255],
            'pickup_address' => ['label' => '上車地址', 'max' => 255],
            'dropoff_address' => ['label' => '下車地址', 'max' => 255],
            'driver_name' => ['label' => '駕駛姓名', 'max' => 255],
            'driver_plate_number' => ['label' => '車牌號碼', 'max' => 255],
            'driver_fleet_number' => ['label' => '隊員編號', 'max' => 255],
            'service_company' => ['label' => '服務公司', 'max' => 255],
            'order_type' => ['label' => '訂單類型', 'max' => 255],
        ];

        foreach ($stringFields as $field => $config) {
            if (isset($data[$field]) && strlen($data[$field]) > $config['max']) {
                $errors[] = "訂單 {$orderNumber} → {$config['label']}: 長度超限 (目前: ".strlen($data[$field])." 字符) - 最大允許 {$config['max']} 字符";
            }
        }

        // 8. 驗證必填欄位
        $requiredFields = [
            'order_number' => '訂單編號',
            'customer_name' => '客戶姓名',
            'customer_phone' => '客戶電話',
            'ride_date' => '用車日期',
            'ride_time' => '用車時間',
            'pickup_address' => '上車地址',
            'dropoff_address' => '下車地址',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "訂單 {$orderNumber} → {$label}: 必填欄位不能為空";
            }
        }

        // 9. 驗證客戶ID是否存在(資料庫必填欄位)
        if (empty($data['customer_id'])) {
            $errors[] = "訂單 {$orderNumber} → 客戶記錄: 無法找到匹配的客戶資料 (姓名: {$data['customer_name']}, 電話: {$data['customer_phone']}) - 請確認客戶資料已存在於系統中";
        }

        return $errors;
    }

    /**
     * 套用預設值
     */
    private function applyDefaultValues($data)
    {
        // 轉換狀態（支援中文輸入）
        if (isset($data['status'])) {
            $data['status'] = $this->fieldMapper->convertOrderStatus($data['status']);
        } else {
            $data['status'] = 'open'; // 預設為待派遣
        }

        $data['companions'] = is_numeric($data['companions'] ?? 0) ? (int) $data['companions'] : 0;
        $data['wheelchair'] = in_array($data['wheelchair'] ?? '', ['是', '否']) ? $data['wheelchair'] : '未知';
        $data['stair_machine'] = in_array($data['stair_machine'] ?? '', ['是', '否']) ? $data['stair_machine'] : '未知';
        $data['special_order'] = false; // 預設非特殊訂單

        return $data;
    }

    /**
     * 驗證日期格式
     */
    private function isValidDate($date)
    {
        if (empty($date)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * 驗證時間格式 (支援 HH:MM 和 HH:MM:SS)
     */
    private function isValidTime($time)
    {
        if (empty($time)) {
            return false;
        }

        // 記錄調試資訊
        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('時間驗證開始', [
                'time' => $time,
                'type' => gettype($time),
            ]);
        }

        // 支援Excel小數時間格式 (0到1之間的小數)
        if (is_numeric($time)) {
            $numericTime = floatval($time);
            if ($numericTime >= 0 && $numericTime <= 1) {
                if (config('app.import_debug_log', false)) {
                    Log::channel('import')->debug('Excel小數時間驗證通過', [
                        'time' => $time,
                        'numeric_value' => $numericTime,
                    ]);
                }

                return true; // Excel小數時間有效
            }
        }

        $timeString = trim(strval($time));

        // 支援 HH:MM:SS 格式 (如: 05:30:00, 23:45:30)
        if (preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $timeString, $matches)) {
            $hour = intval($matches[1]);
            $minute = intval($matches[2]);
            $second = intval($matches[3]);

            $isValid = ($hour >= 0 && $hour <= 23) &&
                      ($minute >= 0 && $minute <= 59) &&
                      ($second >= 0 && $second <= 59);

            if (config('app.import_debug_log', false)) {
                Log::channel('import')->debug('HH:MM:SS 格式驗證', [
                    'time' => $time,
                    'hour' => $hour,
                    'minute' => $minute,
                    'second' => $second,
                    'is_valid' => $isValid,
                ]);
            }

            return $isValid;
        }

        // 支援 HH:MM 格式 (如: 05:30, 23:45)
        if (preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/', $timeString, $matches)) {
            $hour = intval($matches[1]);
            $minute = intval($matches[2]);

            $isValid = ($hour >= 0 && $hour <= 23) &&
                      ($minute >= 0 && $minute <= 59);

            if (config('app.import_debug_log', false)) {
                Log::channel('import')->debug('HH:MM 格式驗證', [
                    'time' => $time,
                    'hour' => $hour,
                    'minute' => $minute,
                    'is_valid' => $isValid,
                ]);
            }

            return $isValid;
        }

        // 如果格式不匹配，記錄錯誤
        if (config('app.import_debug_log', false)) {
            Log::channel('import')->warning('時間格式不支援', [
                'time' => $time,
                'expected_formats' => ['HH:MM:SS', 'HH:MM'],
                'example' => ['05:30:00', '23:45', '12:00:30'],
            ]);
        }

        return false;
    }

    /**
     * 詳細解析資料庫錯誤訊息
     */
    private function simplifyDbError($error)
    {
        // 解析重複資料錯誤
        if (strpos($error, 'Duplicate entry') !== false) {
            if (preg_match("/Duplicate entry '(.+?)' for key '(.+?)'/", $error, $matches)) {
                $value = $matches[1];
                $key = $matches[2];
                if (strpos($key, 'order_number') !== false) {
                    return "訂單編號重複 (值: {$value}) - 此編號已存在於系統中";
                }

                return "重複的資料 (欄位: {$key}, 值: {$value})";
            }

            return '重複的資料';
        }

        // 解析空值錯誤
        if (strpos($error, 'cannot be null') !== false) {
            if (preg_match("/Column '(.+?)' cannot be null/", $error, $matches)) {
                $column = $matches[1];
                $fieldMap = [
                    'order_number' => '訂單編號',
                    'customer_name' => '客戶姓名',
                    'customer_phone' => '客戶電話',
                    'ride_date' => '用車日期',
                    'ride_time' => '用車時間',
                    'pickup_address' => '上車地址',
                    'dropoff_address' => '下車地址',
                    'created_by' => '建立者',
                ];
                $fieldName = $fieldMap[$column] ?? $column;

                return "必要欄位缺失: {$fieldName} 不能為空";
            }

            return '必要欄位缺失';
        }

        // 解析資料長度錯誤
        if (strpos($error, 'Data too long') !== false) {
            if (preg_match("/Data too long for column '(.+?)'/", $error, $matches)) {
                $column = $matches[1];
                $fieldMap = [
                    'order_number' => ['name' => '訂單編號', 'max' => 255],
                    'customer_name' => ['name' => '客戶姓名', 'max' => 255],
                    'customer_phone' => ['name' => '客戶電話', 'max' => 255],
                    'pickup_address' => ['name' => '上車地址', 'max' => 255],
                    'dropoff_address' => ['name' => '下車地址', 'max' => 255],
                ];
                if (isset($fieldMap[$column])) {
                    return "資料長度超限: {$fieldMap[$column]['name']} 超過 {$fieldMap[$column]['max']} 字符限制";
                }

                return "資料長度超限: {$column} 欄位資料過長";
            }

            return '資料長度超出限制';
        }

        // 解析外鍵約束錯誤
        if (strpos($error, 'foreign key constraint') !== false) {
            if (strpos($error, 'customer_id') !== false) {
                return '客戶ID不存在 - 請確認客戶資料是否已建立';
            }
            if (strpos($error, 'driver_id') !== false) {
                return '駕駛ID不存在 - 請確認駕駛資料是否已建立';
            }

            return '關聯資料不存在';
        }

        // 解析enum值錯誤
        if (strpos($error, 'Data truncated') !== false || strpos($error, 'Incorrect') !== false) {
            if (strpos($error, 'status') !== false) {
                // 提取實際的错誤值
                if (preg_match("/values \([^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,[^,]*,\s*([^,]*)/", $error, $matches)) {
                    $actualValue = trim($matches[1]);

                    return "訂單狀態錯誤: 無效值 '{$actualValue}' - 允許的中文值: 待派遣, 已指派, 已候補, 黑名單, 已取消, 一般取消, 別家有車, !取消, X取消 或英文值: open, assigned, bkorder, blocked, cancelled, cancelledOOC, cancelledNOC, cancelledCOTD";
                }

                return '訂單狀態錯誤 - 允許的中文值: 待派遣, 已指派, 已候補, 黑名單, 已取消, 一般取消, 別家有車, !取消, X取消 或英文值: open, assigned, bkorder, blocked, cancelled, cancelledOOC, cancelledNOC, cancelledCOTD';
            }

            return '欄位值不符合規範';
        }

        // 解析整數範圍錯誤
        if (strpos($error, 'Out of range') !== false) {
            if (strpos($error, 'companions') !== false) {
                return '陪同人數超出範圍 - 必須是0-127之間的整數';
            }

            return '數值超出允許範圍';
        }

        // 記錄未能解析的錯誤以便改進
        Log::channel('import')->warning('未能解析的資料庫錯誤', [
            'raw_error' => $error,
        ]);

        return '資料格式錯誤 - 請檢查資料是否符合系統要求';
    }

    /**
     * 檢查資料列是否為空
     */
    private function isRowEmpty($row)
    {
        $rowArray = $row->toArray();

        foreach ($rowArray as $cell) {
            if (! empty(trim($cell))) {
                return false;
            }
        }

        return true;
    }

    /**
     * 決定訂單狀態（結合Excel輸入和駕駛資訊）
     */
    private function determineOrderStatus($row)
    {
        // 檢查是否使用位置映射
        $usePositionMapping = is_array($this->headingRow) &&
                             isset($this->headingRow[0]) &&
                             in_array($this->headingRow[0], ['order_code', 'order_number']);

        if ($usePositionMapping) {
            // 使用位置映射讀取狀態
            $excelStatus = $this->fieldMapper->getValueByPosition($row, 22); // 訂單狀態在位置 22
            $driverFleetNumber = $this->fieldMapper->getValueByPosition($row, 20); // 駕駛隊編在位置 20
        } else {
            // 1. 先檢查Excel中是否有明確的狀態輸入
            $excelStatus = $this->fieldMapper->getRowValue($row, ['status', '訂單狀態', '狀態'], $this->headingRow);
            // 2. 如果Excel沒有狀態，根據駕駛資訊判斷
            $driverFleetNumber = $this->fieldMapper->getRowValue($row, ['driver_fleet_number', '隊員編號', 'assigned_user_id', '駕駛隊編'], $this->headingRow);
        }

        if (! empty($excelStatus)) {
            // 使用Excel中輸入的狀態（支援中文轉換）
            return $this->fieldMapper->convertOrderStatus($excelStatus);
        }

        return $this->determineStatusFromDriver($driverFleetNumber);
    }

    /**
     * 從駕駛隊編判斷訂單狀態
     */
    private function determineStatusFromDriver($driverFleetNumber)
    {
        if (empty($driverFleetNumber)) {
            return 'open'; // 未指派駕駛，狀態為開放
        }

        $driver = Driver::where('fleet_number', $driverFleetNumber)->first();
        if (! $driver) {
            return 'open'; // 找不到駕駛，狀態為開放
        }

        // 根據駕駛狀態決定訂單狀態
        switch ($driver->status) {
            case 'active':
                return 'assigned'; // 已指派
            case 'blacklisted':
                return 'cancelled'; // 駕駛被拉黑，訂單取消
            default:
                return 'open'; // 其他情況狀態為開放
        }
    }

    /**
     * 查找現有客戶（帶快取功能）
     */
    private function findExistingCustomer($name, $phone)
    {
        if (empty($name) && empty($phone)) {
            return null;
        }

        // 建立快取鍵
        $cacheKey = md5($name.'|'.$phone);

        // 檢查快取
        if (isset($this->customerCache[$cacheKey])) {
            return $this->customerCache[$cacheKey];
        }

        try {
            $customer = Customer::where(function ($query) use ($name, $phone) {
                if (! empty($name)) {
                    $query->where('name', $name);
                }
                if (! empty($phone)) {
                    $query->orWhere('phone_number', 'like', '%'.$phone.'%');
                }
            })->first();

            // 快取結果（包括 null）
            $this->customerCache[$cacheKey] = $customer;

            // 定期清理快取以防記憶體溢出
            if (count($this->customerCache) > 1000) {
                // 只保留最近的500個客戶記錄
                $this->customerCache = array_slice($this->customerCache, -500, 500, true);
            }

            return $customer;

        } catch (\Exception $e) {
            Log::channel('import')->warning('客戶查找失敗', [
                'name' => $name,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            // 查找失敗時返回null但不快取
            return null;
        }
    }

    /**
     * 處理單列錯誤
     */
    private function handleRowError(\Exception $e, $rowIndex, $row = null)
    {
        $errorDetails = [
            'row_index' => $rowIndex,
            'error' => $e->getMessage(),
            'error_type' => get_class($e),
        ];

        // 只在除錯模式下記錄完整的行資料和堆疊追蹤
        if (config('app.debug', false)) {
            $errorDetails['row_data'] = $row ? $row->toArray() : null;
            $errorDetails['trace'] = $e->getTraceAsString();
        }

        $this->errorMessages[] = "第 {$rowIndex} 列處理失敗：".$e->getMessage();

        Log::channel('import')->warning('訂單匯入行錯誤', $errorDetails);
    }

    /**
     * 處理嚴重錯誤
     */
    private function handleCriticalError(\Throwable $e, $rowIndex)
    {
        $errorMessage = "第 {$rowIndex} 列發生嚴重錯誤：".$e->getMessage();
        $this->errorMessages[] = $errorMessage;

        Log::channel('import')->error('訂單匯入嚴重錯誤', [
            'row_index' => $rowIndex,
            'error' => $e->getMessage(),
            'error_type' => get_class($e),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 .'MB',
            'trace' => $e->getTraceAsString(),
        ]);

        // 如果是記憶體相關錯誤，強制垃圾回收
        if (strpos($e->getMessage(), 'memory') !== false) {
            $this->performMemoryCleanup(0, true);
        }
    }

    /**
     * 執行記憶體清理
     */
    private function performMemoryCleanup($processedCount, $force = false)
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $memoryPercentage = ($memoryUsage / $memoryLimit) * 100;

        // 當記憶體使用超過 70% 或強制清理時執行
        if ($memoryPercentage > 70 || $force) {
            // 強制垃圾回收
            if (function_exists('gc_collect_cycles')) {
                $collected = gc_collect_cycles();
                Log::channel('import')->debug('執行垃圾回收', [
                    'processed_count' => $processedCount,
                    'memory_before' => round($memoryUsage / 1024 / 1024, 2).'MB',
                    'memory_after' => round(memory_get_usage(true) / 1024 / 1024, 2).'MB',
                    'cycles_collected' => $collected,
                    'memory_percentage' => round($memoryPercentage, 2).'%',
                ]);
            }
        }
    }

    /**
     * 最終清理
     */
    private function performFinalCleanup()
    {
        // 清理大型物件引用和快取
        $this->headingRow = null;
        $this->customerCache = []; // 清理客戶快取

        // 不要設為null，因為可能在後續使用中需要這些服務
        // $this->dateTimeParser = null;
        // $this->addressResolver = null;
        // $this->fieldMapper = null;

        // 強制垃圾回收
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        Log::channel('import')->debug('執行最終清理', [
            'final_memory' => round(memory_get_usage(true) / 1024 / 1024, 2).'MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2).'MB',
        ]);
    }

    /**
     * 獲取記憶體限制
     */
    private function getMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit == -1) {
            return PHP_INT_MAX;
        }

        $value = (int) $memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * 解析並記錄日期
     */
    private function parseAndLogDate($row)
    {
        // 檢查是否使用位置映射
        $usePositionMapping = is_array($this->headingRow) &&
                             isset($this->headingRow[0]) &&
                             in_array($this->headingRow[0], ['order_code', 'order_number']);

        if ($usePositionMapping) {
            $rawDate = $this->fieldMapper->getValueByPosition($row, 6); // 日期在位置 6
        } else {
            $rawDate = $this->fieldMapper->getRowValue($row, ['ride_date', '用車日期', 'date', '日期'], $this->headingRow);
        }

        $parsedDate = $this->dateTimeParser->parseDate($rawDate);

        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('日期解析', [
                'raw_date' => $rawDate,
                'parsed_date' => $parsedDate,
                'using_position_mapping' => $usePositionMapping,
            ]);
        }

        return $parsedDate;
    }

    /**
     * 解析並記錄時間
     */
    private function parseAndLogTime($row)
    {
        // 檢查是否使用位置映射
        $usePositionMapping = is_array($this->headingRow) &&
                             isset($this->headingRow[0]) &&
                             in_array($this->headingRow[0], ['order_code', 'order_number']);

        if ($usePositionMapping) {
            $rawTime = $this->fieldMapper->getValueByPosition($row, 7); // 時間在位置 7
        } else {
            $rawTime = $this->fieldMapper->getRowValue($row, ['ride_time', '用車時間', 'time', '時間'], $this->headingRow);
        }

        $parsedTime = $this->dateTimeParser->parseTime($rawTime);

        if (config('app.import_debug_log', false)) {
            Log::channel('import')->debug('時間解析', [
                'raw_time' => $rawTime,
                'raw_time_type' => gettype($rawTime),
                'parsed_time' => $parsedTime,
                'parsed_time_type' => gettype($parsedTime),
                'using_position_mapping' => $usePositionMapping,
            ]);
        }

        return $parsedTime;
    }

    /**
     * 設置區塊大小，防止記憶體溢位
     */
    public function chunkSize(): int
    {
        // 根據可用記憶體動態調整區塊大小
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $memoryPercentage = ($memoryUsage / $memoryLimit) * 100;

        if ($memoryPercentage > 60) {
            return 100; // 記憶體緊張時使用較小區塊
        } elseif ($memoryPercentage > 30) {
            return 500; // 中等記憶體使用時中等區塊
        } else {
            return 1000; // 記憶體充足時使用較大區塊
        }
    }
}
