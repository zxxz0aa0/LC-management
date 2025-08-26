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
        '訂單編號' => ['order_number', 'order_code', '編號', '單號'],
        '客戶姓名' => ['customer_name', 'name', '姓名', '客戶'],
        '客戶電話' => ['customer_phone', 'phone', 'tel', '電話', '聯絡'],
        '用車日期' => ['ride_date', 'date', '日期'],
        '用車時間' => ['ride_time', 'time', '時間'],
        '上車地址' => ['pickup_address', 'origin_address', '上車地址', '起點地址', 'pickup'],
        '下車地址' => ['dropoff_address', 'destination_address', '下車地址', '終點地址', 'dropoff', 'destination', 'dest'],
        '上車區' => ['pickup_district', 'origin_area', 'pickup_area', '上車區域', '上車區', 'origin_district'],
        '下車區' => ['dropoff_district', 'dest_area', 'dropoff_area', '下車區域', '下車區', 'dest_district'],
        '服務公司' => ['service_company', 'company', '公司'],
        '訂單類型' => ['order_type', 'type', '類型'],
        '身分證' => ['customer_id_number', '身分證', '編號', 'unit_number'],
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
                            'is_empty' => empty($value)
                        ]);
                    }
                    
                    return $value;
                }
            }
        }
        
        // 記錄未找到映射的情況（保留為警告等級，因為這可能是問題）
        Log::warning('欄位映射未找到', [
            'searching_for' => $possibleKeys,
            'available_headers' => $headingRow
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
        
        // 包含關係檢查
        if (strpos($header, $targetKey) !== false || strpos($targetKey, $header) !== false) {
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
                'is_simple_format' => $simpleFieldCount > $fullFieldCount
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
     * 取得建議的標題名稱（用於錯誤提示）
     */
    public function getSuggestedHeaders()
    {
        $suggestions = [];
        foreach ($this->fieldMappings as $chinese => $englishKeys) {
            $suggestions[] = $chinese . ' (' . implode(', ', array_slice($englishKeys, 0, 2)) . ')';
        }
        return $suggestions;
    }
}