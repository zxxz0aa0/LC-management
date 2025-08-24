<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class OrdersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public $successCount = 0;
    public $skipCount = 0;
    public $errorMessages = [];

    public function collection(Collection $rows)
    {
        $rowIndex = 2; // 從第2列開始讀資料（第1列為標題）

        DB::transaction(function () use ($rows, &$rowIndex) {
            foreach ($rows as $row) {
                $this->processRow($row, $rowIndex);
                $rowIndex++;
            }
        });
    }

    private function processRow($row, $rowIndex)
    {
        // 檢查必填欄位
        if (!$this->validateRequiredFields($row, $rowIndex)) {
            return;
        }

        // 檢查訂單編號是否重複
        $orderNumber = trim($row['order_number'] ?? $row['訂單編號'] ?? '');
        if ($this->isDuplicateOrderNumber($orderNumber, $rowIndex)) {
            return;
        }

        // 建立訂單資料
        try {
            $orderData = $this->buildOrderData($row);
            
            // 驗證資料
            if (!$this->validateOrderData($orderData, $rowIndex)) {
                return;
            }

            // 建立訂單
            Order::create($orderData);
            $this->successCount++;

        } catch (\Exception $e) {
            $this->errorMessages[] = "第 {$rowIndex} 列：處理失敗 - " . $e->getMessage();
            $this->skipCount++;
        }
    }

    private function validateRequiredFields($row, $rowIndex)
    {
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
            $value = trim($row[$field] ?? $row[$label] ?? '');
            if (empty($value)) {
                $this->errorMessages[] = "第 {$rowIndex} 列：{$label} 為必填欄位";
                $this->skipCount++;
                return false;
            }
        }

        return true;
    }

    private function isDuplicateOrderNumber($orderNumber, $rowIndex)
    {
        if (Order::where('order_number', $orderNumber)->exists()) {
            $this->errorMessages[] = "第 {$rowIndex} 列：訂單編號 {$orderNumber} 已存在";
            $this->skipCount++;
            return true;
        }
        return false;
    }

    private function buildOrderData($row)
    {
        // 檢測是否為簡化格式（14欄位）
        $isSimpleFormat = $this->detectSimpleFormat($row);
        
        if ($isSimpleFormat) {
            return $this->buildSimpleOrderData($row);
        } else {
            return $this->buildFullOrderData($row);
        }
    }

    private function detectSimpleFormat($row)
    {
        // 檢查是否包含簡化格式的特有欄位組合
        $simpleFields = ['姓名', 'name', '上車區', 'origin_area', '下車區', 'dest_area', '隊員編號', 'assigned_user_id'];
        $fullFields = ['customer_name', '客戶姓名', 'pickup_county', 'dropoff_county'];
        
        $simpleFieldCount = 0;
        $fullFieldCount = 0;
        
        foreach ($simpleFields as $field) {
            if (array_key_exists($field, $row)) $simpleFieldCount++;
        }
        
        foreach ($fullFields as $field) {
            if (array_key_exists($field, $row)) $fullFieldCount++;
        }
        
        return $simpleFieldCount > $fullFieldCount;
    }

    private function buildSimpleOrderData($row)
    {
        // 基本客戶資訊
        $customerName = trim($row['name'] ?? $row['姓名'] ?? '');
        $customerPhone = trim($row['phone'] ?? $row['電話'] ?? '');
        
        // 嘗試從現有客戶資料中找到相符的資料
        $customer = Customer::where('name', $customerName)
            ->orWhere('phone_number', 'like', '%' . $customerPhone . '%')
            ->first();

        // 處理地址格式（簡化格式需要重組完整地址）
        $pickupArea = trim($row['origin_area'] ?? $row['上車區'] ?? '');
        $pickupAddress = trim($row['origin_address'] ?? $row['上車地址'] ?? '');
        $dropoffArea = trim($row['dest_area'] ?? $row['下車區'] ?? '');
        $dropoffAddress = trim($row['dest_address'] ?? $row['下車地址'] ?? '');
        
        // 重組完整地址
        $fullPickupAddress = $this->buildFullAddress($pickupArea, $pickupAddress);
        $fullDropoffAddress = $this->buildFullAddress($dropoffArea, $dropoffAddress);

        return [
            // 基本訂單資訊
            'order_number' => trim($row['order_code'] ?? $row['訂單編號'] ?? ''),
            'order_type' => trim($row['type'] ?? $row['類型'] ?? ''),
            'service_company' => '',
            
            // 日期時間
            'ride_date' => $this->parseDate($row['date'] ?? $row['日期'] ?? ''),
            'ride_time' => $this->parseTime($row['time'] ?? $row['時間'] ?? ''),
            
            // 客戶快照資訊
            'customer_id' => $customer ? $customer->id : null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_id_number' => trim($row['unit_number'] ?? $row['編號'] ?? ''),
            'identity' => '',
            
            // 地址資訊
            'pickup_county' => $this->extractCounty($fullPickupAddress),
            'pickup_district' => $pickupArea,
            'pickup_address' => $fullPickupAddress,
            'dropoff_county' => $this->extractCounty($fullDropoffAddress),
            'dropoff_district' => $dropoffArea,
            'dropoff_address' => $fullDropoffAddress,
            
            // 車輛需求（簡化格式預設值）
            'wheelchair' => '否',
            'stair_machine' => '否',
            'companions' => 0,
            
            // 共乘資訊（簡化格式預設值）
            'carpool_name' => '',
            'carpool_id' => '',
            
            // 駕駛資訊
            'driver_name' => '',
            'driver_fleet_number' => trim($row['assigned_user_id'] ?? $row['隊員編號'] ?? ''),
            'driver_plate_number' => '',
            
            // 狀態資訊
            'status' => $this->determineStatusFromDriver(trim($row['assigned_user_id'] ?? $row['隊員編號'] ?? '')),
            'special_status' => $this->mapSimpleSpecialStatus(trim($row['special_status'] ?? $row['特殊狀態'] ?? '')),
            
            // 其他資訊
            'remark' => trim($row['remark'] ?? $row['備註'] ?? ''),
            'created_by' => auth()->user()->name ?? 'System',
        ];
    }

    private function buildFullOrderData($row)
    {
        // 基本客戶資訊
        $customerName = trim($row['customer_name'] ?? $row['客戶姓名'] ?? '');
        $customerPhone = trim($row['customer_phone'] ?? $row['客戶電話'] ?? '');
        
        // 嘗試從現有客戶資料中找到相符的資料
        $customer = Customer::where('name', $customerName)
            ->orWhere('phone_number', 'like', '%' . $customerPhone . '%')
            ->first();

        return [
            // 基本訂單資訊
            'order_number' => trim($row['order_number'] ?? $row['訂單編號'] ?? ''),
            'order_type' => trim($row['order_type'] ?? $row['訂單類型'] ?? ''),
            'service_company' => trim($row['service_company'] ?? $row['服務公司'] ?? ''),
            
            // 日期時間
            'ride_date' => $this->parseDate($row['ride_date'] ?? $row['用車日期'] ?? ''),
            'ride_time' => $this->parseTime($row['ride_time'] ?? $row['用車時間'] ?? ''),
            
            // 客戶快照資訊
            'customer_id' => $customer ? $customer->id : null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_id_number' => trim($row['customer_id_number'] ?? $row['客戶身分證'] ?? ''),
            'identity' => trim($row['identity'] ?? $row['身份別'] ?? ''),
            
            // 地址資訊
            'pickup_county' => $this->extractCounty($row['pickup_address'] ?? $row['上車地址'] ?? ''),
            'pickup_district' => $this->extractDistrict($row['pickup_address'] ?? $row['上車地址'] ?? ''),
            'pickup_address' => trim($row['pickup_address'] ?? $row['上車地址'] ?? ''),
            'dropoff_county' => $this->extractCounty($row['dropoff_address'] ?? $row['下車地址'] ?? ''),
            'dropoff_district' => $this->extractDistrict($row['dropoff_address'] ?? $row['下車地址'] ?? ''),
            'dropoff_address' => trim($row['dropoff_address'] ?? $row['下車地址'] ?? ''),
            
            // 車輛需求
            'wheelchair' => $this->normalizeYesNo($row['wheelchair'] ?? $row['輪椅'] ?? '否'),
            'stair_machine' => $this->normalizeYesNo($row['stair_machine'] ?? $row['爬梯機'] ?? '否'),
            'companions' => intval($row['companions'] ?? $row['陪同人數'] ?? 0),
            
            // 共乘資訊
            'carpool_name' => trim($row['carpool_name'] ?? $row['共乘姓名'] ?? ''),
            'carpool_id' => trim($row['carpool_id'] ?? $row['共乘身分證'] ?? ''),
            
            // 駕駛資訊
            'driver_name' => trim($row['driver_name'] ?? $row['駕駛姓名'] ?? ''),
            'driver_fleet_number' => trim($row['driver_fleet_number'] ?? $row['駕駛隊編'] ?? ''),
            'driver_plate_number' => trim($row['driver_plate_number'] ?? $row['車牌號碼'] ?? ''),
            
            // 狀態資訊
            'status' => $this->mapStatus($row['status'] ?? $row['訂單狀態'] ?? '可派遣'),
            'special_status' => trim($row['special_status'] ?? $row['特殊狀態'] ?? '一般'),
            
            // 其他資訊
            'remark' => trim($row['remark'] ?? $row['備註'] ?? ''),
            'created_by' => trim($row['created_by'] ?? $row['建立者'] ?? auth()->user()->name ?? 'System'),
        ];
    }

    private function buildFullAddress($area, $address)
    {
        if (empty($area) && empty($address)) return '';
        
        // 如果地址已經包含完整資訊，直接返回
        if (str_contains($address, '市') || str_contains($address, '縣')) {
            return $address;
        }
        
        // 嘗試推斷縣市
        $county = $this->inferCountyFromArea($area);
        
        return trim($county . $area . $address);
    }

    private function inferCountyFromArea($area)
    {
        // 常見區域對應縣市
        $areaMap = [
            '板橋區' => '新北市', '三重區' => '新北市', '新莊區' => '新北市',
            '大安區' => '台北市', '信義區' => '台北市', '松山區' => '台北市',
            '桃園區' => '桃園市', '中壢區' => '桃園市',
            // 可以繼續擴展...
        ];
        
        return $areaMap[$area] ?? '';
    }

    private function determineStatusFromDriver($driverFleetNumber)
    {
        // 根據規格文件：有隊員編號 -> 已指派，無隊員編號 -> 待搶單
        return !empty($driverFleetNumber) ? 'assigned' : 'open';
    }

    private function mapSimpleSpecialStatus($status)
    {
        // 簡化格式特殊狀態對應
        $statusMap = [
            '網頁' => '網頁單',
            'Line' => 'Line',
            '個管' => '個管單',
            '黑名單' => '黑名單',
            '共乘' => '共乘單',
            '' => '一般',
        ];
        
        return $statusMap[$status] ?? '一般';
    }

    private function validateOrderData($data, $rowIndex)
    {
        $validator = Validator::make($data, [
            'order_number' => 'required|string|unique:orders,order_number',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'ride_date' => 'required|date',
            'ride_time' => 'required',
            'pickup_address' => 'required|string',
            'dropoff_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->errorMessages[] = "第 {$rowIndex} 列：{$error}";
            }
            $this->skipCount++;
            return false;
        }

        // 長照地址驗證
        if (!$this->validateLongCareAddress($data, $rowIndex)) {
            return false;
        }

        return true;
    }

    private function validateLongCareAddress($data, $rowIndex)
    {
        $orderType = $data['order_type'];
        $pickupAddress = $data['pickup_address'];
        $dropoffAddress = $data['dropoff_address'];

        if ($orderType === '新北長照') {
            $hasNewTaipei = str_starts_with($pickupAddress, '新北市') || str_starts_with($dropoffAddress, '新北市');
            if (!$hasNewTaipei) {
                $this->errorMessages[] = "第 {$rowIndex} 列：新北長照訂單的上車或下車地址至少一個必須位於新北市";
                $this->skipCount++;
                return false;
            }
        } elseif ($orderType === '台北長照') {
            $hasTaipei = str_starts_with($pickupAddress, '台北市') || str_starts_with($dropoffAddress, '台北市');
            if (!$hasTaipei) {
                $this->errorMessages[] = "第 {$rowIndex} 列：台北長照訂單的上車或下車地址至少一個必須位於台北市";
                $this->skipCount++;
                return false;
            }
        }

        return true;
    }

    private function parseDate($date)
    {
        if (empty($date)) return null;
        
        try {
            // 處理 Excel 數字日期
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            
            // 處理文字日期
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($time)
    {
        if (empty($time)) return null;
        
        try {
            return \Carbon\Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractCounty($address)
    {
        if (preg_match('/^(台北市|新北市|桃園市|台中市|台南市|高雄市|基隆市|新竹市|嘉義市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義縣|屏東縣|宜蘭縣|花蓮縣|台東縣|澎湖縣|金門縣|連江縣)/', $address, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extractDistrict($address)
    {
        if (preg_match('/市(.+?區)/', $address, $matches)) {
            return $matches[1];
        } elseif (preg_match('/縣(.+?鄉|.+?鎮|.+?市)/', $address, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function normalizeYesNo($value)
    {
        $value = trim($value);
        if (in_array($value, ['是', 'Y', 'Yes', '1', 1, true], true)) {
            return '是';
        } elseif (in_array($value, ['未知', 'Unknown', '?'], true)) {
            return '未知';
        }
        return '否';
    }

    private function mapStatus($status)
    {
        $statusMap = [
            '可派遣' => 'open',
            '已指派' => 'assigned', 
            '候補' => 'replacement',
            '已取消' => 'cancelled'
        ];
        
        return $statusMap[$status] ?? 'open';
    }

    public function chunkSize(): int
    {
        return 1000; // 每次處理1000筆，避免記憶體問題
    }
}