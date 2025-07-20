<?php

namespace App\Imports;

use App\Models\Driver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;

class DriversImport implements ToCollection
{
    public $successCount = 0;
    public $skipCount = 0;
    public $errorMessages = [];

    // 狀態對照表（中文 => 英文）
    private $statusMap = [
        '在職' => 'active',
        '啟用' => 'active',
        '正常' => 'active',
        '離職' => 'inactive',
        '停用' => 'inactive',
        '不啟用' => 'inactive',
        '黑名單' => 'blacklist',
        '封鎖' => 'blacklist',
    ];

    public function collection(Collection $rows)
    {
        // 檢查是否有資料
        if ($rows->count() === 0) {
            $this->errorMessages[] = "檔案中沒有資料";
            return;
        }

        // 檢查標題行的位置
        $headerRowIndex = -1;
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            // 尋找包含 "姓名" 的行
            if (in_array('姓名', $rowData)) {
                $headerRowIndex = $index;
                break;
            }
        }

        if ($headerRowIndex === -1) {
            $this->errorMessages[] = "找不到包含 '姓名' 的標題行";
            return;
        }

        // 取得標題行
        $headers = $rows[$headerRowIndex]->toArray();

        // 從標題行的下一行開始讀取資料
        $dataRows = $rows->slice($headerRowIndex + 1);
        $rowIndex = $headerRowIndex + 2; // Excel 行號從 1 開始

        foreach ($dataRows as $row) {
            $rowData = $row->toArray();
            
            // 根據位置對應欄位
            $name = isset($rowData[0]) ? trim($rowData[0]) : '';
            $phone = isset($rowData[1]) ? trim($rowData[1]) : '';
            $idNumber = isset($rowData[2]) ? trim($rowData[2]) : '';
            $fleetNumber = isset($rowData[3]) ? trim($rowData[3]) : '';
            $plateNumber = isset($rowData[4]) ? trim($rowData[4]) : '';
            $carBrand = isset($rowData[5]) ? trim($rowData[5]) : '';
            $carVehicleStyle = isset($rowData[6]) ? trim($rowData[6]) : '';
            $carColor = isset($rowData[7]) ? trim($rowData[7]) : '';
            $lcCompany = isset($rowData[8]) ? trim($rowData[8]) : '';
            $orderType = isset($rowData[9]) ? trim($rowData[9]) : '';
            $serviceType = isset($rowData[10]) ? trim($rowData[10]) : '';
            $statusText = isset($rowData[11]) ? trim($rowData[11]) : 'active';

            // 跳過空白行
            if (!$name && !$phone && !$idNumber) {
                $rowIndex++;
                continue;
            }

            // 必填欄位檢查
            if (!$name) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少姓名";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            if (!$phone) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少手機";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            if (!$idNumber) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少身分證字號";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            // 檢查是否已存在相同手機或身分證的駕駛
            $existingDriver = Driver::where('phone', $phone)
                ->orWhere('id_number', $idNumber)
                ->first();

            // 處理狀態
            $status = $this->statusMap[$statusText] ?? 'active';

            $data = [
                'name' => $name,
                'phone' => $phone,
                'id_number' => $idNumber,
                'fleet_number' => $fleetNumber,
                'plate_number' => $plateNumber,
                'car_brand' => $carBrand,
                'car_vehicle_style' => $carVehicleStyle,
                'car_color' => $carColor,
                'lc_company' => $lcCompany,
                'order_type' => $orderType,
                'service_type' => $serviceType,
                'status' => $status,
            ];

            try {
                if ($existingDriver) {
                    // 更新現有駕駛
                    $existingDriver->update($data);
                    $this->successCount++;
                } else {
                    // 建立新駕駛
                    Driver::create($data);
                    $this->successCount++;
                }
            } catch (\Exception $e) {
                $this->errorMessages[] = "第 {$rowIndex} 列：資料庫錯誤 - " . $e->getMessage();
                $this->skipCount++;
            }

            $rowIndex++;
        }
    }
}