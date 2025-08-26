<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 日期時間解析服務
 * 處理各種Excel日期時間格式轉換
 */
class DateTimeParser
{
    /**
     * 解析日期
     */
    public function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        if (config('app.import_debug_log', false)) {
            Log::debug('日期解析開始', [
                'original' => $date,
                'type' => gettype($date)
            ]);
        }

        try {
            // 處理 Excel 序列號格式
            if (is_numeric($date)) {
                $excelDate = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays(intval($date) - 2);
                $result = $excelDate->format('Y-m-d');
                
                if (config('app.import_debug_log', false)) {
                    Log::debug('Excel序列號日期解析成功', [
                        'original' => $date,
                        'parsed' => $result
                    ]);
                }
                
                return $result;
            }

            // 處理字串格式
            if (is_string($date)) {
                // 移除中文字符
                $cleanDate = str_replace(['年', '月', '日'], ['-', '-', ''], $date);
                
                // 嘗試多種格式
                $formats = [
                    'Y-m-d',
                    'Y/m/d',
                    'd/m/Y',
                    'm/d/Y',
                    'Y-m-d H:i:s',
                    'Y/m/d H:i:s'
                ];
                
                foreach ($formats as $format) {
                    try {
                        $parsed = Carbon::createFromFormat($format, $cleanDate);
                        $result = $parsed->format('Y-m-d');
                        
                        if (config('app.import_debug_log', false)) {
                            Log::debug('字串日期解析成功', [
                                'original' => $date,
                                'format' => $format,
                                'parsed' => $result
                            ]);
                        }
                        
                        return $result;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                // 最後嘗試 Carbon 自動解析
                $result = Carbon::parse($cleanDate)->format('Y-m-d');
                
                if (config('app.import_debug_log', false)) {
                    Log::debug('Carbon自動日期解析成功', [
                        'original' => $date,
                        'parsed' => $result
                    ]);
                }
                
                return $result;
            }

        } catch (\Exception $e) {
            Log::error('日期解析失敗', [
                'original' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }

        return null;
    }

    /**
     * 解析時間（支援 AM/PM 格式）
     */
    public function parseTime($time)
    {
        if (empty($time)) {
            return null;
        }
        
        if (config('app.import_debug_log', false)) {
            Log::debug('時間解析開始', [
                'original' => $time,
                'type' => gettype($time),
                'is_numeric' => is_numeric($time)
            ]);
        }
        
        try {
            // 處理 Excel 數字時間格式 (0到1之間的小數)
            if (is_numeric($time)) {
                $numericTime = floatval($time);
                
                if ($numericTime >= 0 && $numericTime <= 1) {
                    $totalSeconds = $numericTime * 86400;
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = $totalSeconds % 60;
                    
                    $result = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    
                    if (config('app.import_debug_log', false)) {
                        Log::debug('Excel數字時間解析成功', [
                            'original' => $time,
                            'numeric_value' => $numericTime,
                            'total_seconds' => $totalSeconds,
                            'parsed' => $result
                        ]);
                    }
                    
                    return $result;
                }
            }
            
            $timeString = trim(strval($time));
            
            // 標準時間格式檢查
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $timeString)) {
                if (substr_count($timeString, ':') === 1) {
                    $timeString .= ':00';
                }
                
                if (config('app.import_debug_log', false)) {
                    Log::debug('標準時間格式解析成功', [
                        'original' => $time,
                        'parsed' => $timeString
                    ]);
                }
                
                return $timeString;
            }
            
            // 純小時格式
            if (preg_match('/^(\d{1,2})$/', $timeString, $matches)) {
                $hour = intval($matches[1]);
                if ($hour >= 0 && $hour <= 23) {
                    $result = sprintf('%02d:00:00', $hour);
                    
                    if (config('app.import_debug_log', false)) {
                        Log::debug('純小時格式解析成功', [
                            'original' => $time,
                            'parsed' => $result
                        ]);
                    }
                    
                    return $result;
                }
            }
            
            // 檢查是否包含 AM/PM 標記
            $isAM = preg_match('/\b(AM|am|上午|早上)\b/i', $timeString);
            $isPM = preg_match('/\b(PM|pm|下午|晚上)\b/i', $timeString);
            
            // 移除中文字元和AM/PM標記
            $cleanTimeString = preg_replace('/\b(AM|PM|am|pm|上午|下午|早上|晚上|點|分|時)\b/i', '', $timeString);
            $cleanTimeString = trim($cleanTimeString);
            
            // 提取時間數字 - 支援 HH:MM:SS 或 HH:MM 格式
            if (preg_match('/(\d{1,2})[:\.](\d{2})(?:[:\.](\d{2}))?/', $cleanTimeString, $matches)) {
                $hour = intval($matches[1]);
                $minute = intval($matches[2]);
                $second = isset($matches[3]) ? intval($matches[3]) : 0;
                
                // 處理 12小時制轉換
                if ($isAM || $isPM) {
                    if ($isPM && $hour < 12) {
                        $hour += 12;
                    } elseif ($isAM && $hour == 12) {
                        $hour = 0;
                    }
                }
                
                // 驗證時間範圍
                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 && $second >= 0 && $second <= 59) {
                    $result = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
                    
                    if (config('app.import_debug_log', false)) {
                        Log::debug('AM/PM時間解析成功', [
                            'original' => $time,
                            'cleaned' => $cleanTimeString,
                            'is_am' => $isAM,
                            'is_pm' => $isPM,
                            'hour_before' => intval($matches[1]),
                            'hour_after' => $hour,
                            'minute' => $minute,
                            'second' => $second,
                            'parsed' => $result
                        ]);
                    }
                    
                    return $result;
                }
            }
            
            // 最後嘗試使用 Carbon 解析
            $result = Carbon::parse($timeString)->format('H:i:s');
            
            if (config('app.import_debug_log', false)) {
                Log::debug('Carbon時間解析成功', [
                    'original' => $time,
                    'parsed' => $result
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('時間解析失敗', [
                'original' => $time,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}