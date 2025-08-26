<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Driver;
use App\Services\DateTimeParser;
use App\Services\TaiwanAddressResolver;
use App\Services\ExcelFieldMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

    public function __construct()
    {
        $this->dateTimeParser = new DateTimeParser();
        $this->addressResolver = new TaiwanAddressResolver();
        $this->fieldMapper = new ExcelFieldMapper();
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // 手動讀取第一列作為標題
        $this->headingRow = $rows->first()->toArray();
        
        // 記錄實際讀取到的標題，使用匯入專用頻道
        Log::channel('import')->info('Excel 標題檢查', [
            'raw_headers' => $this->headingRow,
            'headers_count' => count($this->headingRow)
        ]);

        // 從第二列開始處理資料
        $dataRows = $rows->skip(1);
        $rowIndex = 2; // 從第2列開始讀資料（第1列為標題）

        // 檢測匯入格式
        $isSimpleFormat = $this->fieldMapper->detectSimpleFormat($this->headingRow);
        
        Log::channel('import')->info('匯入格式檢測', [
            'format' => $isSimpleFormat ? '簡化格式' : '完整格式',
            'row_count' => $dataRows->count()
        ]);

        foreach ($dataRows as $row) {
            try {
                // 檢查資料是否有效
                if ($this->isRowEmpty($row)) {
                    $this->skipCount++;
                    continue;
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

            } catch (\Exception $e) {
                $this->skipCount++;
                $this->errorMessages[] = "第 {$rowIndex} 列處理失敗：" . $e->getMessage();
                
                Log::channel('import')->error('訂單匯入錯誤', [
                    'row_index' => $rowIndex,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $rowIndex++;
        }
        
        // 記錄最終統計
        Log::channel('import')->info('匯入完成統計', [
            'success_count' => $this->successCount,
            'skip_count' => $this->skipCount,
            'error_count' => count($this->errorMessages),
            'total_processed' => $rowIndex - 2
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
        if (!empty($errors)) {
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
        if (!empty($errors)) {
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
                'dropoff_address' => $dropoffAddress
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
                'dropoff_address' => $dropoffFullAddress
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
                'dropoff_address' => $dropoffAddress
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
                'dropoff_address' => $dropoffAddress
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
     * 創建訂單
     */
    private function createOrder($orderData)
    {
        try {
            // 檢查重複訂單
            $existingOrder = Order::where('order_number', $orderData['order_number'])->first();
            if ($existingOrder) {
                $this->errorMessages[] = "訂單編號 {$orderData['order_number']} 已存在，跳過匯入";
                return false;
            }

            Order::create($orderData);
            return true;
        } catch (\Exception $e) {
            Log::channel('import')->error('訂單創建失敗', [
                'order_number' => $orderData['order_number'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 檢查資料列是否為空
     */
    private function isRowEmpty($row)
    {
        $rowArray = $row->toArray();
        
        foreach ($rowArray as $cell) {
            if (!empty(trim($cell))) {
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
        if (!$driver) {
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
     * 查找現有客戶
     */
    private function findExistingCustomer($name, $phone)
    {
        if (empty($name) && empty($phone)) {
            return null;
        }

        return Customer::where(function($query) use ($name, $phone) {
            if (!empty($name)) {
                $query->where('name', $name);
            }
            if (!empty($phone)) {
                $query->orWhere('phone_number', 'like', '%' . $phone . '%');
            }
        })->first();
    }

    /**
     * 設置區塊大小，防止記憶體溢位
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}