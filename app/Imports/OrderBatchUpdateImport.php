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
    public $successCount = 0; // 實際更新訂單筆數（含共乘同步）
    public $processedRowCount = 0; // 已處理的 Excel 行數

    public $skipCount = 0;

    public $errorMessages = [];

    public $carpoolSyncCount = 0; // 共乘同步更新筆數

    private $driverCache = [];

    private $preValidationErrors = []; // 預檢查錯誤

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // 記錄起始時間與記憶體
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        Log::info('批量更新匯入開始', [
            'total_rows' => $rows->count(),
            'memory_before' => $this->formatBytes($startMemory),
        ]);

        // 跳過標題列
        $dataRows = $rows->skip(1);

        // 預先檢查
        $this->preValidate($dataRows);

        foreach ($dataRows as $index => $row) {
            try {
                $this->processRow($row, $index + 2); // +2 為跳過標題行後的實際 Excel 行數
            } catch (\Exception $e) {
                $this->skipCount++;
                $this->errorMessages[] = '第'.($index + 2).' 行更新失敗：'.$e->getMessage();
                Log::warning('批量更新行處理失敗', [
                    'row' => $index + 2,
                    'order_number' => trim($row[0] ?? ''),
                    'reason' => $e->getMessage(),
                    'data' => $row->toArray(),
                ]);
            }
        }

        // 記錄結束時間與記憶體
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $processingTime = round($endTime - $startTime, 2);
        $memoryUsed = $endMemory - $startMemory;

        Log::info('批量更新匯入完成', [
            'processed_row_count' => $this->processedRowCount, // Excel 行數
            'total_order_count' => $this->successCount, // 實際訂單筆數（含同步）
            'carpool_sync_count' => $this->carpoolSyncCount, // 共乘同步
            'skip_count' => $this->skipCount,
            'processing_time' => $processingTime.'秒',
            'memory_used' => $this->formatBytes($memoryUsed),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
        ]);

        // 額外總結：統計處理、成功、跳過與錯誤
        Log::info('批量更新摘要', [
            'excel_rows' => $rows->count() - 1, // 扣除標題
            'processed_row_count' => $this->processedRowCount,
            'success_count' => $this->successCount,
            'skip_count' => $this->skipCount,
            'carpool_sync_count' => $this->carpoolSyncCount,
            'errors' => $this->errorMessages,
        ]);
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        // Excel 欄位對應
        // A欄：訂單編號 (order_number)
        // E欄：車隊員編號 (fleet_number)
        // H欄：媒合時間 (match_time)
        // O欄：狀態 (status)

        $orderNumber = trim($row[0] ?? ''); // A欄
        $fleetNumber = trim($row[4] ?? ''); // E欄 (index 4 = 第5欄)
        $matchTime = trim($row[7] ?? '');   // H欄 (index 7 = 第8欄)
        $status = trim($row[14] ?? '');     // O欄 (index 14 = 第15欄)

        // 驗證必填欄位
        if (empty($orderNumber)) {
            throw new \Exception('訂單編號不能為空');
        }

        // 查詢訂單
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            throw new \Exception("找不到訂單編號：{$orderNumber}");
        }

        DB::transaction(function () use ($order, $fleetNumber, $matchTime, $status, $rowNumber, $orderNumber) {
            $updateData = [];

            // 更新駕駛資料
            if (! empty($fleetNumber)) {
                $driver = $this->getDriverByFleetNumber($fleetNumber);
                if ($driver) {
                    $updateData['driver_id'] = $driver->id;
                    $updateData['driver_name'] = $driver->name;
                    $updateData['driver_plate_number'] = $driver->plate_number;
                    $updateData['driver_fleet_number'] = $driver->fleet_number;
                } else {
                    throw new \Exception("找不到車隊員編號：{$fleetNumber}");
                }
            }

            // 更新媒合時間
            if (! empty($matchTime) && $matchTime !== '-') {
                try {
                    $updateData['match_time'] = $this->parseDateTime($matchTime);
                } catch (\Exception $e) {
                    throw new \Exception("媒合時間格式錯誤：{$matchTime}");
                }
            }

            // 更新狀態
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
                    // 若已是英文狀態值，直接使用
                    $updateData['status'] = $status;
                } else {
                    throw new \Exception("未知的狀態值：{$status}（允許值：待搶單、已派單、已取消、已候補、已完成）");
                }
            }

            // 執行更新
            if (! empty($updateData)) {
                // 記錄更新人員
                $updateData['updated_by'] = auth()->id();

                // 如果需要同步更新共乘訂單，取出同組訂單
                $ordersToUpdate = $this->getCarpoolGroupOrders($order);
                $updatedCount = 0;

                foreach ($ordersToUpdate as $orderToUpdate) {
                    // 驗證更新是否成功
                    $updateResult = $orderToUpdate->update($updateData);

                    if ($updateResult === false) {
                        throw new \Exception("訂單 {$orderToUpdate->order_number} 更新失敗");
                    }

                    $updatedCount++;

                    // 記錄實際更新內容
                    Log::info('批量更新訂單', [
                        'excel_row' => $rowNumber,
                        'order_number' => $orderToUpdate->order_number,
                        'order_id' => $orderToUpdate->id,
                        'updated_fields' => array_keys($updateData),
                        'updated_data' => $updateData, // 實際更新的欄位內容
                        'is_carpool_sync' => count($ordersToUpdate) > 1,
                    ]);
                }

                // 計數統計修正
                $this->processedRowCount++; // Excel 行數 +1
                $this->successCount += $updatedCount; // 訂單數累加
                // 如有共乘同步，記錄同步筆數
                if (count($ordersToUpdate) > 1) {
                    $this->carpoolSyncCount += ($updatedCount - 1); // 扣除主單的本身
                    Log::info("第{$rowNumber} 行共乘組同步更新完成，共更新 {$updatedCount} 筆訂單");
                }
            } else {
                Log::notice('批量更新跳過：無可更新欄位', [
                    'excel_row' => $rowNumber,
                    'order_number' => $orderNumber,
                ]);
                throw new \Exception('沒有要更新的資料（E/H/O 欄至少填寫一項）');
            }
        });
    }

    private function getDriverByFleetNumber(string $fleetNumber): ?Driver
    {
        // 使用快取減少查詢
        if (! isset($this->driverCache[$fleetNumber])) {
            $this->driverCache[$fleetNumber] = Driver::where('fleet_number', $fleetNumber)->first();
        }

        return $this->driverCache[$fleetNumber];
    }

    /**
     * 取得共乘群組中的所有有效訂單
     */
    private function getCarpoolGroupOrders(Order $order): Collection
    {
        // 如果不是共乘訂單，只返回自己
        if (empty($order->carpool_group_id)) {
            return collect([$order]);
        }

        // 查詢同一個共乘群組的有效訂單
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

        // 如仍無法解析，試 Carbon 的自動解析
        try {
            return Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            throw new \Exception("無法解析日期時間：{$dateTimeString}");
        }
    }

    /**
     * 預先檢查：驗證訂單編號與車隊編號是否存在
     */
    private function preValidate(Collection $dataRows): void
    {
        Log::info('批量更新預檢查開始');

        // 收集所有訂單編號
        $orderNumbers = $dataRows->map(function ($row) {
            return trim($row[0] ?? '');
        })->filter()->unique();

        // 收集所有車隊編號
        $fleetNumbers = $dataRows->map(function ($row) {
            return trim($row[4] ?? ''); // E欄
        })->filter()->unique();

        // 檢查訂單編號是否存在
        if ($orderNumbers->isNotEmpty()) {
            $existingOrders = Order::whereIn('order_number', $orderNumbers->toArray())
                ->pluck('order_number');

            $missingOrders = $orderNumbers->diff($existingOrders);

            if ($missingOrders->isNotEmpty()) {
                Log::warning('預檢查發現不存在的訂單編號', [
                    'missing_orders' => $missingOrders->toArray(),
                    'count' => $missingOrders->count(),
                ]);
            }
        }

        // 檢查車隊編號是否存在（也同步到快取）
        if ($fleetNumbers->isNotEmpty()) {
            $drivers = Driver::whereIn('fleet_number', $fleetNumbers->toArray())->get();

            foreach ($drivers as $driver) {
                $this->driverCache[$driver->fleet_number] = $driver;
            }

            $existingFleetNumbers = collect($this->driverCache)->keys();
            $missingDrivers = $fleetNumbers->diff($existingFleetNumbers);

            if ($missingDrivers->isNotEmpty()) {
                Log::warning('預檢查發現不存在的車隊員編號', [
                    'missing_fleet_numbers' => $missingDrivers->toArray(),
                    'count' => $missingDrivers->count(),
                ]);
            }
        }

        Log::info('批量更新預檢查完成', [
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
        return 100; // 每次處理 100 筆
    }
}
