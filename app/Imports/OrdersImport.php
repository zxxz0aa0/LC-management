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
            // 手動讀取第一列作為標題
            $this->headingRow = $rows->first()->toArray();

            // 記錄實際讀取到的標題，使用匯入專用頻道
            Log::channel('import')->info('Excel 標題檢查', [
                'raw_headers' => $this->headingRow,
                'headers_count' => count($this->headingRow),
            ]);

            // 從第二列開始處理資料
            $dataRows = $rows->skip(1);
            $rowIndex = 2; // 從第2列開始讀資料（第1列為標題）

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
        $pickupArea = $this->fieldMapper->getRowValue($row, ['pickup_district', 'origin_area', 'pickup_area', '上車區域', '上車區', 'origin_district'], $this->headingRow);
        $dropoffArea = $this->fieldMapper->getRowValue($row, ['dropoff_district', 'dest_area', 'dropoff_area', '下車區域', '下車區', 'dest_district'], $this->headingRow);
        $pickupAddress = $this->fieldMapper->getRowValue($row, ['pickup_address', 'origin_address', '上車地址', '起點地址', 'pickup'], $this->headingRow);
        $dropoffAddress = $this->fieldMapper->getRowValue($row, ['dropoff_address', 'destination_address', '下車地址', '終點地址', 'dropoff', 'destination', 'dest'], $this->headingRow);

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

        $pickupFullAddress = $this->addressResolver->buildFullAddress($pickupArea, $pickupAddress);
        $dropoffFullAddress = $this->addressResolver->buildFullAddress($dropoffArea, $dropoffAddress);

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
        $nameKeys = $format === 'simple'
            ? ['name', '姓名', 'customer_name']
            : ['customer_name', 'name', '客戶姓名', '姓名'];

        return [
            'name' => $this->fieldMapper->getRowValue($row, $nameKeys, $this->headingRow),
            'phone' => $this->fieldMapper->getRowValue($row, ['customer_phone', 'phone', 'tel', '客戶電話', '電話', '聯絡'], $this->headingRow),
            'id_number' => $this->fieldMapper->getRowValue($row, ['customer_id_number', '身分證', '編號', 'unit_number'], $this->headingRow),
            'identity' => $this->fieldMapper->getRowValue($row, ['identity', '身分別'], $this->headingRow),
        ];
    }

    /**
     * 建立基礎訂單資料
     */
    private function buildBaseOrderData($row, $customerData, $customer)
    {
        return [
            // 基本訂單資訊
            'order_number' => $this->fieldMapper->getRowValue($row, ['order_number', 'order_code', '訂單編號', '編號', '單號'], $this->headingRow),
            'order_type' => $this->fieldMapper->getRowValue($row, ['order_type', '訂單類型', 'type', '類型'], $this->headingRow),
            'service_company' => $this->fieldMapper->getRowValue($row, ['service_company', '服務公司', 'company', '公司'], $this->headingRow),

            // 日期時間
            'ride_date' => $this->dateTimeParser->parseDate($this->fieldMapper->getRowValue($row, ['ride_date', '用車日期', 'date', '日期'], $this->headingRow)),
            'ride_time' => $this->dateTimeParser->parseTime($this->fieldMapper->getRowValue($row, ['ride_time', '用車時間', 'time', '時間'], $this->headingRow)),

            // 客戶快照資訊
            'customer_id' => $customer ? $customer->id : null,
            'customer_name' => $customerData['name'],
            'customer_phone' => $customerData['phone'],
            'customer_id_number' => $customerData['id_number'],
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
            'status' => $this->determineStatusFromDriver($this->fieldMapper->getRowValue($row, ['driver_fleet_number', '隊員編號', 'assigned_user_id'], $this->headingRow)),

            // 其他資訊
            'special_status' => $this->fieldMapper->getRowValue($row, ['special_status', '特殊狀態', '特殊標記'], $this->headingRow),
            'remark' => $this->fieldMapper->getRowValue($row, ['remark', '備註', '註記', '說明'], $this->headingRow),
            'created_by' => auth()->user()->name ?? 'System',
        ];
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
            $this->errorMessages[] = "訂單 {$orderData['order_number']} 資料庫錯誤：".$this->simplifyDbError($e->getMessage());

            Log::channel('import')->error('訂單創建資料庫錯誤', [
                'order_number' => $orderData['order_number'] ?? 'unknown',
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'unknown',
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

            // 驗證日期格式
            if (isset($cleanData['ride_date'])) {
                if (! $this->isValidDate($cleanData['ride_date'])) {
                    $this->errorMessages[] = "訂單 {$orderData['order_number']} 日期格式錯誤：{$cleanData['ride_date']}";

                    return false;
                }
            }

            // 驗證時間格式
            if (isset($cleanData['ride_time'])) {
                if (! $this->isValidTime($cleanData['ride_time'])) {
                    $this->errorMessages[] = "訂單 {$orderData['order_number']} 時間格式錯誤：{$cleanData['ride_time']}";

                    return false;
                }
            }

            // 設置預設值
            $cleanData['status'] = $cleanData['status'] ?? 'open';
            $cleanData['companions'] = is_numeric($cleanData['companions'] ?? 0) ? (int) $cleanData['companions'] : 0;
            $cleanData['wheelchair'] = in_array($cleanData['wheelchair'] ?? '', ['是', '否']) ? $cleanData['wheelchair'] : '未知';
            $cleanData['stair_machine'] = in_array($cleanData['stair_machine'] ?? '', ['是', '否']) ? $cleanData['stair_machine'] : '未知';

            return $cleanData;

        } catch (\Exception $e) {
            $this->errorMessages[] = "訂單 {$orderData['order_number']} 資料清理失敗：".$e->getMessage();

            return false;
        }
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
     * 驗證時間格式
     */
    private function isValidTime($time)
    {
        if (empty($time)) {
            return false;
        }

        return preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }

    /**
     * 簡化資料庫錯誤訊息
     */
    private function simplifyDbError($error)
    {
        if (strpos($error, 'Duplicate entry') !== false) {
            return '重複的資料';
        } elseif (strpos($error, 'cannot be null') !== false) {
            return '必要欄位缺失';
        } elseif (strpos($error, 'Data too long') !== false) {
            return '資料長度超出限制';
        } elseif (strpos($error, 'foreign key constraint') !== false) {
            return '關聯資料不存在';
        } else {
            return '資料格式錯誤';
        }
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
     * 從駕駛隊編判斷訂單狀態
     */
    private function determineStatusFromDriver($driverFleetNumber)
    {
        if (empty($driverFleetNumber)) {
            return 'pending'; // 未指派駕駛
        }

        $driver = Driver::where('fleet_number', $driverFleetNumber)->first();
        if (! $driver) {
            return 'pending'; // 找不到駕駛
        }

        // 根據駕駛狀態決定訂單狀態
        switch ($driver->status) {
            case 'active':
                return 'assigned'; // 已指派
            case 'blacklisted':
                return 'cancelled'; // 駕駛被拉黑，訂單取消
            default:
                return 'pending'; // 其他情況待處理
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
