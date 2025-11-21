<?php

namespace App\Imports;

use App\Models\Driver;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class OrderBatchUpdateImport implements ToCollection, WithChunkReading
{
    public $successCount = 0; // 實際更新的訂單數（含共乘同步）

    public $processedRowCount = 0; // 成功處理的 Excel 行數

    public $skipCount = 0;

    public $errorMessages = [];

    public $carpoolSyncCount = 0; // 共乘同步更新的訂單數

    private $driverCache = [];

    private $preValidationErrors = []; // 預檢查錯誤

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // 記錄開始處理時間和記憶體使用
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        Log::info('批量更新匯入開始', [
            'total_rows' => $rows->count(),
            'memory_before' => $this->formatBytes($startMemory),
        ]);

        // 跳過標題行
        $dataRows = $rows->skip(1);

        // 批次預檢查
        $this->preValidate($dataRows);

        foreach ($dataRows as $index => $row) {
            try {
                $this->processRow($row, $index + 2); // +2 因為跳過標題行且從1開始計數
            } catch (\Exception $e) {
                $this->skipCount++;
                $this->errorMessages[] = '第 '.($index + 2).' 行處理失敗：'.$e->getMessage();
                Log::error('批量更新行處理失敗', [
                    'row' => $index + 2,
                    'data' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 記錄結束處理時間和記憶體使用
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $processingTime = round($endTime - $startTime, 2);
        $memoryUsed = $endMemory - $startMemory;

        Log::info('批量更新匯入完成', [
            'processed_row_count' => $this->processedRowCount, // Excel 行數
            'total_order_count' => $this->successCount, // 實際訂單數
            'carpool_sync_count' => $this->carpoolSyncCount, // 共乘同步數
            'skip_count' => $this->skipCount,
            'processing_time' => $processingTime.'秒',
            'memory_used' => $this->formatBytes($memoryUsed),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
        ]);
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        // 根據 Excel 欄位對應：
        // A欄：訂單編號 (order_number)
        // E欄：隊員編號 (fleet_number)
        // H欄：媒合時間 (match_time)
        // O欄：狀態 (status)

        $orderNumber = trim($row[0] ?? ''); // A欄
        $fleetNumber = trim($row[4] ?? ''); // E欄 (index 4 = 第5欄)
        $matchTime = trim($row[7] ?? '');   // H欄 (index 7 = 第8欄)
        $status = trim($row[14] ?? '');     // O欄 (index 14 = 第15欄)

        // 驗證必要欄位
        if (empty($orderNumber)) {
            throw new \Exception('訂單編號不能為空');
        }

        // 查詢訂單
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            throw new \Exception("找不到訂單編號：{$orderNumber}");
        }

        DB::transaction(function () use ($order, $fleetNumber, $matchTime, $status, $rowNumber) {
            $updateData = [];

            // 處理駕駛資訊
            if (! empty($fleetNumber)) {
                $driver = $this->getDriverByFleetNumber($fleetNumber);
                if ($driver) {
                    $updateData['driver_id'] = $driver->id;
                    $updateData['driver_name'] = $driver->name;
                    $updateData['driver_plate_number'] = $driver->plate_number;
                    $updateData['driver_fleet_number'] = $driver->fleet_number;
                } else {
                    throw new \Exception("找不到隊員編號：{$fleetNumber}");
                }
            }

            // 處理媒合時間
            if (! empty($matchTime) && $matchTime !== '-') {
                try {
                    $updateData['match_time'] = $this->parseDateTime($matchTime);
                } catch (\Exception $e) {
                    throw new \Exception("媒合時間格式錯誤：{$matchTime}");
                }
            }

            // 處理狀態
            if (! empty($status)) {
                $statusMapping = [
                    '待搶單' => 'open',
                    '已指派' => 'assigned',
                    '已取消' => 'cancelled',
                    '已候補' => 'bkorder',
                    '已完成' => 'completed',
                ];

                // 移除 HTML 標籤並取得純文字
                $cleanStatus = strip_tags($status);
                if (array_key_exists($cleanStatus, $statusMapping)) {
                    $updateData['status'] = $statusMapping[$cleanStatus];
                } elseif (in_array($status, array_values($statusMapping))) {
                    // 如果是英文狀態，直接使用
                    $updateData['status'] = $status;
                } else {
                    throw new \Exception("未知的狀態值：{$status}（允許的狀態：待搶單、已指派、已取消、已候補、已完成）");
                }
            }

            // 執行更新
            if (! empty($updateData)) {
                // 記錄更新人員
                $updateData['updated_by'] = auth()->id();

                // 找出所有需要同步更新的共乘訂單
                $ordersToUpdate = $this->getCarpoolGroupOrders($order);
                $updatedCount = 0;

                foreach ($ordersToUpdate as $orderToUpdate) {
                    // 驗證更新是否成功
                    $updateResult = $orderToUpdate->update($updateData);

                    if ($updateResult === false) {
                        throw new \Exception("訂單 {$orderToUpdate->order_number} 更新失敗");
                    }

                    $updatedCount++;

                    // 增強日誌：記錄實際更新的資料內容
                    Log::info('成功更新訂單', [
                        'excel_row' => $rowNumber,
                        'order_number' => $orderToUpdate->order_number,
                        'order_id' => $orderToUpdate->id,
                        'updated_fields' => array_keys($updateData),
                        'updated_data' => $updateData, // 記錄實際更新的資料
                        'is_carpool_sync' => count($ordersToUpdate) > 1,
                    ]);
                }

                // 計數邏輯修正
                $this->processedRowCount++; // Excel 行數 +1
                $this->successCount += $updatedCount; // 訂單數累加

                // 如果是共乘同步，記錄同步數量
                if (count($ordersToUpdate) > 1) {
                    $this->carpoolSyncCount += ($updatedCount - 1); // 扣除主訂單本身
                    Log::info("第 {$rowNumber} 行：共乘組合同步更新完成，共更新 {$updatedCount} 筆訂單");
                }
            } else {
                throw new \Exception('沒有需要更新的資料（E、H、O 欄位至少需填寫一項）');
            }
        });
    }

    private function getDriverByFleetNumber(string $fleetNumber): ?Driver
    {
        // 使用快取避免重複查詢
        if (! isset($this->driverCache[$fleetNumber])) {
            $this->driverCache[$fleetNumber] = Driver::where('fleet_number', $fleetNumber)->first();
        }

        return $this->driverCache[$fleetNumber];
    }

    /**
     * 取得共乘組合中的所有訂單
     */
    private function getCarpoolGroupOrders(Order $order): Collection
    {
        // 如果不是共乘訂單，只返回自己
        if (empty($order->carpool_group_id)) {
            return collect([$order]);
        }

        // 查詢同一個共乘群組的所有訂單
        return Order::where('carpool_group_id', $order->carpool_group_id)
            ->where('is_group_dissolved', false) // 排除已解散的群組
            ->get();
    }

    private function parseDateTime(string $dateTimeString): Carbon
    {
        // 支援多種日期時間格式
        $formats = [
            'Y-m-d H:i:s',
            'Y/m/d H:i:s',
            'Y-m-d H:i',
            'Y/m/d H:i',
            'Y-m-d',
            'Y/m/d',
            'H:i:s',
            'H:i',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateTimeString);
            } catch (\Exception $e) {
                continue;
            }
        }

        // 如果都無法解析，嘗試 Carbon 的自動解析
        try {
            return Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            throw new \Exception("無法解析日期時間格式：{$dateTimeString}");
        }
    }

    /**
     * 批次預檢查：驗證訂單編號和駕駛編號是否存在
     */
    private function preValidate(Collection $dataRows): void
    {
        Log::info('開始批次預檢查');

        // 收集所有訂單編號
        $orderNumbers = $dataRows->map(function ($row) {
            return trim($row[0] ?? '');
        })->filter()->unique();

        // 收集所有駕駛編號
        $fleetNumbers = $dataRows->map(function ($row) {
            return trim($row[4] ?? ''); // E欄
        })->filter()->unique();

        // 檢查訂單編號是否存在
        if ($orderNumbers->isNotEmpty()) {
            $existingOrders = Order::whereIn('order_number', $orderNumbers->toArray())
                ->pluck('order_number');

            $missingOrders = $orderNumbers->diff($existingOrders);

            if ($missingOrders->isNotEmpty()) {
                Log::warning('預檢查：發現不存在的訂單編號', [
                    'missing_orders' => $missingOrders->toArray(),
                    'count' => $missingOrders->count(),
                ]);
            }
        }

        // 檢查駕駛編號是否存在（預先載入到快取）
        if ($fleetNumbers->isNotEmpty()) {
            $drivers = Driver::whereIn('fleet_number', $fleetNumbers->toArray())->get();

            foreach ($drivers as $driver) {
                $this->driverCache[$driver->fleet_number] = $driver;
            }

            $existingFleetNumbers = collect($this->driverCache)->keys();
            $missingDrivers = $fleetNumbers->diff($existingFleetNumbers);

            if ($missingDrivers->isNotEmpty()) {
                Log::warning('預檢查：發現不存在的隊員編號', [
                    'missing_fleet_numbers' => $missingDrivers->toArray(),
                    'count' => $missingDrivers->count(),
                ]);
            }
        }

        Log::info('批次預檢查完成', [
            'total_order_numbers' => $orderNumbers->count(),
            'total_fleet_numbers' => $fleetNumbers->count(),
            'cached_drivers' => count($this->driverCache),
        ]);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    public function chunkSize(): int
    {
        return 100; // 每次處理100行
    }
}
