<?php

namespace App\Imports;

use App\Models\Landmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LandmarksImport implements ToCollection, WithHeadingRow
{
    public $successCount = 0;
    public $skipCount = 0;
    public $errorMessages = [];

    // 分類對照表（中文 => 英文）
    private $categoryMap = [
        '醫療' => 'medical',
        '交通' => 'transport',
        '教育' => 'education',
        '政府機關' => 'government',
        '政府' => 'government',
        '商業' => 'commercial',
        '一般' => 'general',
    ];

    public function collection(Collection $rows)
    {
        $rowIndex = 2; // 從第2列開始讀資料（第1列為標題）

        foreach ($rows as $row) {
            $name = trim($row['地標名稱'] ?? '');
            $address = trim($row['地址'] ?? '');

            // 必填欄位檢查
            if (!$name) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少地標名稱";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            if (!$address) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少地址";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            $city = trim($row['城市'] ?? '');
            $district = trim($row['區域'] ?? '');

            if (!$city) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少城市";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            if (!$district) {
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
            $categoryText = trim($row['分類'] ?? '');
            $category = $this->categoryMap[$categoryText] ?? null;

            if (!$category) {
                $validCategories = implode('、', array_keys($this->categoryMap));
                $this->errorMessages[] = "第 {$rowIndex} 列：分類格式錯誤，請使用：{$validCategories}";
                $this->skipCount++;
                $rowIndex++;
                continue;
            }

            // 處理座標
            $longitude = trim($row['經度'] ?? '');
            $latitude = trim($row['緯度'] ?? '');
            $coordinates = null;

            if ($longitude && $latitude) {
                if (!is_numeric($longitude) || !is_numeric($latitude)) {
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
            $isActiveText = trim($row['是否啟用'] ?? '1');
            $isActive = in_array($isActiveText, ['1', '啟用', 'true', 'TRUE']) ? 1 : 0;

            $data = [
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'district' => $district,
                'category' => $category,
                'description' => trim($row['描述'] ?? ''),
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
                $this->errorMessages[] = "第 {$rowIndex} 列：資料庫錯誤 - " . $e->getMessage();
                $this->skipCount++;
            }

            $rowIndex++;
        }
    }
}