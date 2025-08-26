<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 台灣地址解析服務
 * 處理地址解析、縣市區域對應等功能
 */
class TaiwanAddressResolver
{
    /**
     * 從地址中提取縣市
     */
    public function extractCounty($address)
    {
        if (empty($address)) {
            return '';
        }

        $counties = [
            '台北市', '新北市', '桃園市', '台中市', '台南市', '高雄市',
            '基隆市', '新竹市', '嘉義市',
            '新竹縣', '苗栗縣', '彰化縣', '南投縣', '雲林縣', '嘉義縣',
            '屏東縣', '宜蘭縣', '花蓮縣', '台東縣', '澎湖縣', '金門縣', '連江縣'
        ];

        foreach ($counties as $county) {
            if (strpos($address, $county) === 0) {
                return $county;
            }
        }

        return '';
    }

    /**
     * 從地址中提取區域/鄉鎮市
     */
    public function extractDistrict($address)
    {
        if (empty($address)) {
            return '';
        }
        
        if (config('app.import_debug_log', false)) {
            Log::debug('地址區域解析開始', [
                'address' => $address
            ]);
        }
        
        // 匹配縣市+區域的模式
        $patterns = [
            '/(?:台北市|新北市|桃園市|台中市|台南市|高雄市|基隆市|新竹市|嘉義市)(.+?區)/', // 直轄市+區
            '/(?:新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義縣|屏東縣|宜蘭縣|花蓮縣|台東縣|澎湖縣|金門縣|連江縣)(.+?鄉|.+?鎮|.+?市)/', // 縣+鄉鎮市
            '/市(.+?區)/', // 任何市+區
            '/縣(.+?鄉|.+?鎮|.+?市)/', // 任何縣+鄉鎮市
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address, $matches)) {
                $district = $matches[1];
                
                if (config('app.import_debug_log', false)) {
                    Log::debug('地址區域解析成功', [
                        'address' => $address,
                        'pattern' => $pattern,
                        'extracted_district' => $district
                    ]);
                }
                
                return $district;
            }
        }
        
        Log::warning('地址區域解析失敗', [
            'address' => $address,
            'tried_patterns' => count($patterns)
        ]);
        
        return '';
    }

    /**
     * 從區域推斷縣市
     */
    public function inferCountyFromArea($area)
    {
        if (empty($area)) {
            return '';
        }
        
        // 詳細記錄輸入值用於除錯
        if (config('app.import_debug_log', false)) {
            Log::debug('區域映射開始', [
                'original_area' => $area,
                'area_type' => gettype($area),
                'area_length' => strlen($area),
                'area_trim' => trim($area),
                'area_bytes' => bin2hex($area)
            ]);
        }
        
        // 移除可能的縣市前綴並標準化
        $cleanArea = trim($area);
        $prefixes = ['台北市', '新北市', '桃園市', '台中市', '台南市', '高雄市', '基隆市', '新竹市', '嘉義市'];
        foreach ($prefixes as $prefix) {
            if (strpos($cleanArea, $prefix) === 0) {
                $cleanArea = str_replace($prefix, '', $cleanArea);
                break;
            }
        }
        $cleanArea = trim($cleanArea);
        
        // 自動補上「區」字尾（如果沒有的話）
        if (!empty($cleanArea) && !preg_match('/[區鄉鎮市]$/', $cleanArea)) {
            $cleanArea .= '區';
        }
        
        if (config('app.import_debug_log', false)) {
            Log::debug('區域清理結果', [
                'original' => $area,
                'cleaned' => $cleanArea,
                'added_suffix' => !preg_match('/[區鄉鎮市]$/', trim($area))
            ]);
        }
        
        // 智能匹配：優先匹配獨特區域，再處理重複區域
        $uniqueAreas = $this->getUniqueAreas();
        
        // 先檢查獨特區域
        if (isset($uniqueAreas[$cleanArea])) {
            $result = $uniqueAreas[$cleanArea];
            if (config('app.import_debug_log', false)) {
                Log::debug('獨特區域對應成功', [
                    'original' => $area,
                    'clean_area' => $cleanArea,
                    'county' => $result
                ]);
            }
            return $result;
        }
        
        // 處理重複區域
        $contextBasedMapping = $this->getContextBasedMapping();
        
        if (isset($contextBasedMapping[$cleanArea])) {
            $possibleCounties = $contextBasedMapping[$cleanArea];
            $result = $possibleCounties[0]; // 預設選擇第一個選項
            
            Log::warning('重複區域處理', [
                'original' => $area,
                'clean_area' => $cleanArea,
                'possible_counties' => $possibleCounties,
                'selected' => $result,
                'note' => '可能需要更多上下文判斷正確縣市'
            ]);
            
            return $result;
        }
        
        // 嘗試模糊匹配
        $allAreas = array_merge(array_keys($uniqueAreas), array_keys($contextBasedMapping));
        $similarAreas = [];
        
        foreach ($allAreas as $knownArea) {
            if (strpos($knownArea, $cleanArea) !== false || strpos($cleanArea, $knownArea) !== false) {
                $similarAreas[] = $knownArea;
            }
        }
        
        Log::warning('區域對應失敗', [
            'original' => $area,
            'clean_area' => $cleanArea,
            'similar_areas' => $similarAreas,
            'total_known_areas' => count($allAreas),
            'suggestion' => '請檢查Excel中的區域名稱格式'
        ]);
        
        return '';
    }

    /**
     * 組合完整地址
     */
    public function buildFullAddress($area, $address)
    {
        if (empty($area) && empty($address)) {
            return '';
        }
        
        if (empty($area)) {
            return trim($address);
        }
        
        if (empty($address)) {
            $county = $this->inferCountyFromArea($area);
            return trim($county . $area);
        }
        
        // 嘗試推斷縣市
        $county = $this->inferCountyFromArea($area);
        
        return trim($county . $area . $address);
    }

    /**
     * 獲取獨特區域對應表（只屬於一個縣市的區域）
     */
    private function getUniqueAreas()
    {
        return [
            // 台北市獨有
            '大同區' => '台北市', '松山區' => '台北市', '萬華區' => '台北市', '士林區' => '台北市',
            '北投區' => '台北市', '內湖區' => '台北市', '南港區' => '台北市', '文山區' => '台北市',
            
            // 新北市獨有 
            '板橋區' => '新北市', '三重區' => '新北市', '中和區' => '新北市', '永和區' => '新北市',
            '新莊區' => '新北市', '新店區' => '新北市', '樹林區' => '新北市', '鶯歌區' => '新北市',
            '三峽區' => '新北市', '淡水區' => '新北市', '汐止區' => '新北市', '瑞芳區' => '新北市',
            '土城區' => '新北市', '蘆洲區' => '新北市', '五股區' => '新北市', '泰山區' => '新北市',
            '林口區' => '新北市', '深坑區' => '新北市', '石碇區' => '新北市', '坪林區' => '新北市',
            '三芝區' => '新北市', '石門區' => '新北市', '八里區' => '新北市', '平溪區' => '新北市',
            '雙溪區' => '新北市', '貢寮區' => '新北市', '金山區' => '新北市', '萬里區' => '新北市',
            '烏來區' => '新北市',
            
            // 桃園市
            '桃園區' => '桃園市', '中壢區' => '桃園市', '大溪區' => '桃園市', '楊梅區' => '桃園市',
            '蘆竹區' => '桃園市', '大園區' => '桃園市', '龜山區' => '桃園市', '八德區' => '桃園市',
            '龍潭區' => '桃園市', '平鎮區' => '桃園市', '新屋區' => '桃園市', '觀音區' => '桃園市',
            '復興區' => '桃園市',
            
            // 台中市獨有
            '北屯區' => '台中市', '西屯區' => '台中市', '南屯區' => '台中市', '太平區' => '台中市',
            '大里區' => '台中市', '霧峰區' => '台中市', '烏日區' => '台中市', '豐原區' => '台中市',
            '后里區' => '台中市', '石岡區' => '台中市', '東勢區' => '台中市', '和平區' => '台中市',
            '新社區' => '台中市', '潭子區' => '台中市', '大雅區' => '台中市', '神岡區' => '台中市',
            '大肚區' => '台中市', '沙鹿區' => '台中市', '龍井區' => '台中市', '梧棲區' => '台中市',
            '清水區' => '台中市', '大甲區' => '台中市', '外埔區' => '台中市',
            
            // 台南市獨有
            '中西區' => '台南市', '安平區' => '台南市', '安南區' => '台南市', '永康區' => '台南市',
            '歸仁區' => '台南市', '新化區' => '台南市', '左鎮區' => '台南市', '玉井區' => '台南市',
            '楠西區' => '台南市', '南化區' => '台南市', '仁德區' => '台南市', '關廟區' => '台南市',
            '龍崎區' => '台南市', '官田區' => '台南市', '麻豆區' => '台南市', '佳里區' => '台南市',
            '西港區' => '台南市', '七股區' => '台南市', '將軍區' => '台南市', '學甲區' => '台南市',
            '北門區' => '台南市', '新營區' => '台南市', '後壁區' => '台南市', '白河區' => '台南市',
            '東山區' => '台南市', '六甲區' => '台南市', '下營區' => '台南市', '柳營區' => '台南市',
            '鹽水區' => '台南市', '善化區' => '台南市', '大內區' => '台南市', '山上區' => '台南市',
            '新市區' => '台南市', '安定區' => '台南市',
            
            // 高雄市獨有
            '新興區' => '高雄市', '前金區' => '高雄市', '苓雅區' => '高雄市', '鹽埕區' => '高雄市',
            '鼓山區' => '高雄市', '旗津區' => '高雄市', '前鎮區' => '高雄市', '三民區' => '高雄市',
            '左營區' => '高雄市', '楠梓區' => '高雄市', '小港區' => '高雄市', '仁武區' => '高雄市',
            '大社區' => '高雄市', '岡山區' => '高雄市', '路竹區' => '高雄市', '阿蓮區' => '高雄市',
            '田寮區' => '高雄市', '燕巢區' => '高雄市', '橋頭區' => '高雄市', '梓官區' => '高雄市',
            '彌陀區' => '高雄市', '永安區' => '高雄市', '湖內區' => '高雄市', '鳳山區' => '高雄市',
            '大寮區' => '高雄市', '林園區' => '高雄市', '鳥松區' => '高雄市', '大樹區' => '高雄市',
            '旗山區' => '高雄市', '美濃區' => '高雄市', '六龜區' => '高雄市', '內門區' => '高雄市',
            '杉林區' => '高雄市', '甲仙區' => '高雄市', '桃源區' => '高雄市', '那瑪夏區' => '高雄市',
            '茂林區' => '高雄市', '茄萣區' => '高雄市',
            
            // 基隆市獨有
            '仁愛區' => '基隆市', '安樂區' => '基隆市', '暖暖區' => '基隆市', '七堵區' => '基隆市',
            
            // 新竹市獨有
            '香山區' => '新竹市',
        ];
    }

    /**
     * 獲取重複區域的上下文映射表
     */
    private function getContextBasedMapping()
    {
        return [
            '中正區' => ['台北市', '基隆市'],
            '信義區' => ['台北市', '基隆市'], 
            '中山區' => ['台北市', '基隆市'],
            '大安區' => ['台北市', '台中市'],
            '東區' => ['台中市', '台南市', '新竹市', '嘉義市'],
            '南區' => ['台中市', '台南市'],
            '北區' => ['台中市', '台南市', '新竹市'],
            '西區' => ['台中市', '嘉義市'],
            '中區' => ['台中市'],
        ];
    }
}