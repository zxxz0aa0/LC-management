<?php

namespace App\Imports;

use App\Models\Landmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;

class LandmarksImport implements ToCollection
{
    public $successCount = 0;

    public $skipCount = 0;

    public $errorMessages = [];

    // 分類對照表（中文 => 英文）
    private $categoryMap = [
        '醫院' => 'hospital',
        '診所' => 'clinic',
        '醫療' => 'hospital', // 向下相容：預設將醫療歸類為醫院
        '交通' => 'transport',
        '教育' => 'education',
        '政府機關' => 'government',
        '政府' => 'government',
        '商業' => 'commercial',
        '一般' => 'general',
    ];

    public function collection(Collection $rows)
    {
        // 檢查是否有資料
        if ($rows->count() === 0) {
            $this->errorMessages[] = '檔案中沒有資料';

            return;
        }

        // 檢查標題行的位置
        $headerRowIndex = -1;
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            // 尋找包含 "地標名稱" 的行
            if (in_array('地標名稱', $rowData)) {
                $headerRowIndex = $index;
                break;
            }
        }

        if ($headerRowIndex === -1) {
            $this->errorMessages[] = "找不到包含 '地標名稱' 的標題行";

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
            $address = isset($rowData[1]) ? trim($rowData[1]) : '';
            $city = isset($rowData[2]) ? trim($rowData[2]) : '';
            $district = isset($rowData[3]) ? trim($rowData[3]) : '';
            $categoryText = isset($rowData[4]) ? trim($rowData[4]) : '';
            $description = isset($rowData[5]) ? trim($rowData[5]) : '';
            $longitude = isset($rowData[6]) ? trim($rowData[6]) : '';
            $latitude = isset($rowData[7]) ? trim($rowData[7]) : '';
            $isActiveText = isset($rowData[8]) ? trim($rowData[8]) : '1';

            // 跳過空白行
            if (! $name && ! $address && ! $city) {
                $rowIndex++;

                continue;
            }

            // 必填欄位檢查
            if (! $name) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少地標名稱";
                $this->skipCount++;
                $rowIndex++;

                continue;
            }

            if (! $address) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少地址";
                $this->skipCount++;
                $rowIndex++;

                continue;
            }

            if (! $city) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少城市";
                $this->skipCount++;
                $rowIndex++;

                continue;
            }

            if (! $district) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少區域";
                $this->skipCount++;
                $rowIndex++;

                continue;
            }

            // 檢查是否已存在相同名稱和地址的地標
            $existingLandmark = Landmark::where('name', $name)
                ->where('address', $address)
                ->where('city', $city)
                ->first();

            // 處理分類
            $category = $this->categoryMap[$categoryText] ?? null;

            if (! $category) {
                $validCategories = implode('、', array_keys($this->categoryMap));
                $this->errorMessages[] = "第 {$rowIndex} 列：分類格式錯誤，請使用：{$validCategories}";
                $this->skipCount++;
                $rowIndex++;

                continue;
            }

            // 處理座標
            $coordinates = null;

            if ($longitude && $latitude) {
                if (! is_numeric($longitude) || ! is_numeric($latitude)) {
                    $this->errorMessages[] = "第 {$rowIndex} 列：座標格式錯誤（必須為數字）";
                    $this->skipCount++;
                    $rowIndex++;

                    continue;
                }

                $coordinates = [
                    'lng' => (float) $longitude,
                    'lat' => (float) $latitude,
                ];
            }

            // 處理是否啟用
            $isActive = in_array($isActiveText, ['1', '啟用', 'true', 'TRUE']) ? 1 : 0;

            $data = [
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'district' => $district,
                'category' => $category,
                'description' => $description,
                'coordinates' => $coordinates,
                'is_active' => $isActive,
                'usage_count' => 0, // 新建地標使用次數為0
                'created_by' => Auth::user()->name ?? '系統匯入',
            ];

            try {
                if ($existingLandmark) {
                    // 更新現有地標
                    $existingLandmark->update($data);
                } else {
                    // 建立新地標
                    Landmark::create($data);
                }

                $this->successCount++;
            } catch (\Exception $e) {
                $this->errorMessages[] = "第 {$rowIndex} 列：資料庫錯誤 - ".$e->getMessage();
                $this->skipCount++;
            }

            $rowIndex++;
        }
    }
}
