<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Excel 欄位映射服務
 * 處理中英文欄位映射和欄位匹配邏輯
 */
class ExcelFieldMapper
{
    /**
     * 中文關鍵字映射表
     */
    private $fieldMappings = [
        '訂單編號' => ['order_number', 'order_code', '單號'],
        '客戶姓名' => ['customer_name', 'name', '姓名', '客戶'],
        '客戶電話' => ['customer_phone', 'phone', 'tel', '電話', '聯絡'],
        '用車日期' => ['ride_date', 'date', '日期'],
        '用車時間' => ['ride_time', 'time', '時間'],
        '上車地址' => ['pickup_address', 'origin_address', '上車地址', '起點地址'],
        '下車地址' => ['dropoff_address', 'destination_address', '下車地址', '終點地址', 'destination', 'dest'],
        '上車區' => ['pickup_district', 'origin_area', 'pickup_area', '上車區域', '上車區', 'origin_district'],
        '下車區' => ['dropoff_district', 'dest_area', 'dropoff_area', '下車區域', '下車區', 'dest_district'],
        '服務公司' => ['service_company', 'company', '公司'],
        '訂單類型' => ['order_type', 'type', '類型'],
        '身分證' => ['customer_id_number', '身分證', 'unit_number', '客戶身分證'],
        '身分別' => ['identity', '身份別'],
        '輪椅' => ['wheelchair', '輪椅需求'],
        '爬梯機' => ['stair_machine', '爬梯機需求'],
        '陪同人數' => ['companions', '陪伴者'],
        '共乘姓名' => ['carpool_name', '共乘者姓名'],
        '共乘身分證' => ['carpool_id', '共乘者身分證'],
        '駕駛姓名' => ['driver_name', '司機姓名'],
        '隊員編號' => ['driver_fleet_number', 'assigned_user_id', '駕駛隊編'],
        '車牌號碼' => ['driver_plate_number', '車牌'],
        '特殊狀態' => ['special_status', '特殊標記'],
        '訂單狀態' => ['status', '狀態'],
        '備註' => ['remark', '註記', '說明'],
        '建立者' => ['created_by', '建立人'],
    ];

    /**
     * 訂單狀態中英文對照表
     */
    private $statusMappings = [
        '待派遣' => 'open',
        '已指派' => 'assigned',
        '已候補' => 'bkorder',
        '黑名單' => 'blocked',
        '已取消' => 'cancelled',
        '一般取消' => 'cancelled',
        '別家有車' => 'cancelledOOC',
        '!取消' => 'cancelledNOC',
        'X取消' => 'cancelledCOTD',
        // 英文值也保持支援
        'open' => 'open',
        'assigned' => 'assigned',
        'bkorder' => 'bkorder',
        'blocked' => 'blocked',
        'cancelled' => 'cancelled',
        'cancelledOOC' => 'cancelledOOC',
        'cancelledNOC' => 'cancelledNOC',
        'cancelledCOTD' => 'cancelledCOTD',
    ];

    /**
     * 從資料列中取得指定欄位的值
     */
    public function getRowValue($row, array $possibleKeys, $headingRow)
    {
        $rowArray = $row->toArray();

        // 遍歷所有標題，尋找匹配的欄位
        foreach ($headingRow as $index => $header) {
            foreach ($possibleKeys as $key) {
                if ($this->isHeaderMatch($header, $key)) {
                    $value = isset($rowArray[$index]) ? trim($rowArray[$index]) : '';

                    // 只在除錯模式或有設定時記錄詳細映射日誌
                    if (config('app.import_debug_log', false)) {
                        Log::debug('欄位映射匹配', [
                            'excel_header' => $header,
                            'header_index' => $index,
                            'mapped_to' => $key,
                            'possible_keys' => $possibleKeys,
                            'raw_value' => isset($rowArray[$index]) ? $rowArray[$index] : null,
                            'trimmed_value' => $value,
                            'is_empty' => empty($value),
                        ]);
                    }

                    return $value;
                }
            }
        }

        // 記錄未找到映射的情況（保留為警告等級，因為這可能是問題）
        Log::warning('欄位映射未找到', [
            'searching_for' => $possibleKeys,
            'available_headers' => $headingRow,
        ]);

        return '';
    }

    /**
     * 檢查標題是否匹配目標鍵名
     */
    public function isHeaderMatch($header, $targetKey)
    {
        $header = trim($header);
        $targetKey = trim($targetKey);

        // 完全匹配
        if ($header === $targetKey) {
            return true;
        }

        // 中文關鍵字映射檢查
        foreach ($this->fieldMappings as $chinese => $keywords) {
            if ($targetKey === $chinese || in_array($targetKey, $keywords)) {
                // 檢查中文標題
                if (strpos($header, $chinese) !== false) {
                    return true;
                }
                // 檢查英文關鍵字
                foreach ($keywords as $keyword) {
                    if (strpos($header, $keyword) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 偵測是否為簡化格式
     */
    public function detectSimpleFormat($headingRow)
    {
        // 檢查標題列是否包含簡化格式的特有欄位組合
        $simpleFields = ['姓名', 'name', '上車區', 'origin_area', '下車區', 'dest_area', '隊員編號', 'assigned_user_id'];
        $fullFields = ['customer_name', '客戶姓名', 'pickup_county', 'dropoff_county'];

        $simpleFieldCount = 0;
        $fullFieldCount = 0;

        foreach ($simpleFields as $field) {
            foreach ($headingRow as $header) {
                if ($this->isHeaderMatch($header, $field)) {
                    $simpleFieldCount++;
                    break;
                }
            }
        }

        foreach ($fullFields as $field) {
            foreach ($headingRow as $header) {
                if ($this->isHeaderMatch($header, $field)) {
                    $fullFieldCount++;
                    break;
                }
            }
        }

        if (config('app.import_debug_log', false)) {
            Log::debug('格式偵測', [
                'simple_field_count' => $simpleFieldCount,
                'full_field_count' => $fullFieldCount,
                'is_simple_format' => $simpleFieldCount > $fullFieldCount,
            ]);
        }

        return $simpleFieldCount > $fullFieldCount;
    }

    /**
     * 驗證必填欄位
     */
    public function validateRequiredFields($row, $headingRow, $rowIndex)
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

        $errors = [];
        foreach ($requiredFields as $field => $label) {
            $value = $this->getRowValue($row, [$field, $label], $headingRow);
            if (empty($value)) {
                $errors[] = "第 {$rowIndex} 列：{$label} 為必填欄位";
            }
        }

        return $errors;
    }

    /**
     * 取得欄位映射表
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }

    /**
     * 新增自訂欄位映射
     */
    public function addFieldMapping($chineseLabel, array $englishKeys)
    {
        $this->fieldMappings[$chineseLabel] = $englishKeys;
    }

    /**
     * 轉換訂單狀態（中文 → 英文）
     */
    public function convertOrderStatus($status)
    {
        if (empty($status)) {
            return 'open'; // 預設為待派遣
        }

        $status = trim($status);

        // 直接對照
        if (isset($this->statusMappings[$status])) {
            return $this->statusMappings[$status];
        }

        // 模糊匹配（包含關係檢查）
        foreach ($this->statusMappings as $chinese => $english) {
            if (strpos($status, $chinese) !== false || strpos($chinese, $status) !== false) {
                return $english;
            }
        }

        // 如果都不匹配，返回原值（用於錯誤檢測）
        return $status;
    }

    /**
     * 取得狀態對照表
     */
    public function getStatusMappings()
    {
        return $this->statusMappings;
    }

    /**
     * 檢測行是否為有效標題行
     */
    public function isValidHeaderRow($row)
    {
        $rowArray = is_array($row) ? $row : $row->toArray();
        $validFieldCount = 0;
        $dataLikeCount = 0;
        $totalCells = count($rowArray);

        // 如果行數太少，可能有問題
        if ($totalCells < 3) {
            return false;
        }

        foreach ($rowArray as $cell) {
            $cell = trim($cell);

            // 跳過空值
            if (empty($cell)) {
                continue;
            }

            // 檢查是否包含預期的欄位關鍵字
            $isValidField = false;
            foreach ($this->fieldMappings as $chineseLabel => $englishKeys) {
                if (strpos($cell, $chineseLabel) !== false) {
                    $isValidField = true;
                    break;
                }
                foreach ($englishKeys as $englishKey) {
                    if (strpos($cell, $englishKey) !== false) {
                        $isValidField = true;
                        break 2;
                    }
                }
            }

            if ($isValidField) {
                $validFieldCount++;
            }

            // 檢查是否像資料內容
            if ($this->looksLikeData($cell)) {
                $dataLikeCount++;
            }
        }

        // 判斷規則：
        // 1. 至少有3個有效欄位關鍵字
        // 2. 資料特徵的比例不能太高
        $validFieldRatio = $validFieldCount / max(1, $totalCells);
        $dataLikeRatio = $dataLikeCount / max(1, $totalCells);

        if (class_exists('Illuminate\Support\Facades\Log') && app()->bound('log')) {
            Log::debug('標題行檢測', [
                'total_cells' => $totalCells,
                'valid_field_count' => $validFieldCount,
                'data_like_count' => $dataLikeCount,
                'valid_field_ratio' => $validFieldRatio,
                'data_like_ratio' => $dataLikeRatio,
                'is_valid_header' => $validFieldCount >= 3 && $dataLikeRatio < 0.5,
            ]);
        }

        return $validFieldCount >= 3 && $dataLikeRatio < 0.5;
    }

    /**
     * 檢測內容是否看起來像資料而不是標題
     */
    private function looksLikeData($content)
    {
        $content = trim($content);

        // 空值不算資料特徵
        if (empty($content)) {
            return false;
        }

        // 訂單編號格式 (如: NTPC5502025080600001300)
        if (preg_match('/^[A-Z]{2,4}\d{10,20}$/', $content)) {
            return true;
        }

        // 電話號碼格式
        if (preg_match('/^0\d{8,9}$/', $content)) {
            return true;
        }

        // 身分證格式
        if (preg_match('/^[A-Z]\d{9}$/', $content)) {
            return true;
        }

        // 日期格式
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $content)) {
            return true;
        }

        // 時間格式或小數 (Excel 時間會轉成小數)
        if (preg_match('/^\d{1,2}:\d{2}$/', $content) ||
            (is_numeric($content) && $content > 0 && $content < 1)) {
            return true;
        }

        // 純數字 (但排除年份)
        if (is_numeric($content) && $content != (int) date('Y')) {
            return true;
        }

        // 很長的地址 (超過15個字元且包含地址關鍵字)
        if (strlen($content) > 15 &&
            (strpos($content, '市') !== false || strpos($content, '區') !== false ||
             strpos($content, '路') !== false || strpos($content, '街') !== false)) {
            return true;
        }

        return false;
    }

    /**
     * 取得預設欄位順序映射（無標題行時使用）
     */
    public function getDefaultFieldMapping($formatType = 'auto')
    {
        if ($formatType === 'simple') {
            // 簡化格式的預設順序
            return [
                0 => 'order_number',      // 訂單編號
                1 => 'customer_name',     // 姓名
                2 => 'customer_phone',    // 電話
                3 => 'customer_id_number', // 編號
                4 => 'order_type',        // 類型
                5 => 'ride_date',         // 日期
                6 => 'ride_time',         // 時間
                7 => 'pickup_district',   // 上車區
                8 => 'pickup_address',    // 上車地址
                9 => 'dropoff_district',  // 下車區
                10 => 'dropoff_address',  // 下車地址
                11 => 'remark',           // 備註
                12 => 'driver_fleet_number', // 隊員編號
                13 => 'special_status',   // 特殊狀態
            ];
        } else {
            // 完整格式的預設順序
            return [
                0 => 'order_number',         // 訂單編號
                1 => 'customer_name',        // 客戶姓名
                2 => 'customer_phone',       // 客戶電話
                3 => 'customer_id_number',   // 客戶身分證
                4 => 'order_type',           // 訂單類型
                5 => 'service_company',      // 服務公司
                6 => 'ride_date',            // 用車日期
                7 => 'ride_time',            // 用車時間
                8 => 'pickup_county',        // 上車縣市
                9 => 'pickup_district',      // 上車區域
                10 => 'pickup_address',      // 上車地址
                11 => 'dropoff_county',      // 下車縣市
                12 => 'dropoff_district',    // 下車區域
                13 => 'dropoff_address',     // 下車地址
                14 => 'wheelchair',          // 輪椅
                15 => 'stair_machine',       // 爬梯機
                16 => 'companions',          // 陪同人數
                17 => 'carpool_name',        // 共乘姓名
                18 => 'carpool_id',          // 共乘身分證
                19 => 'driver_name',         // 駕駛姓名
                20 => 'driver_fleet_number', // 駕駛隊編
                21 => 'driver_plate_number', // 車牌號碼
                22 => 'status',              // 訂單狀態
                23 => 'special_status',      // 特殊狀態
                24 => 'remark',              // 備註
                25 => 'created_by',          // 建立者
                26 => 'identity',            // 身份別
                27 => 'created_at',          // 建立時間
            ];
        }
    }

    /**
     * 使用欄位位置取得值（無標題行時使用）
     */
    public function getValueByPosition($row, $position, $fieldName = '')
    {
        $rowArray = is_array($row) ? $row : $row->toArray();

        if (isset($rowArray[$position])) {
            $value = trim($rowArray[$position]);

            // 記錄位置映射（僅在除錯模式）
            if (config('app.import_debug_log', false)) {
                Log::debug('位置映射', [
                    'position' => $position,
                    'field_name' => $fieldName,
                    'value' => $value,
                ]);
            }

            return $value;
        }

        return '';
    }

    /**
     * 取得建議的標題名稱（用於錯誤提示）
     */
    public function getSuggestedHeaders()
    {
        $suggestions = [];
        foreach ($this->fieldMappings as $chinese => $englishKeys) {
            $suggestions[] = $chinese.' ('.implode(', ', array_slice($englishKeys, 0, 2)).')';
        }

        return $suggestions;
    }
}
