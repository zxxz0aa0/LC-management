<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BatchOrderService
{
    protected $carpoolGroupService;

    protected $orderNumberService;

    public function __construct(CarpoolGroupService $carpoolGroupService, OrderNumberService $orderNumberService)
    {
        $this->carpoolGroupService = $carpoolGroupService;
        $this->orderNumberService = $orderNumberService;
    }

    /**
     * 建立多天訂單
     */
    public function createMultipleDaysOrders($orderData, $dates)
    {
        // 驗證日期陣列
        $this->validateDates($dates);

        // 台北長照日期限制驗證（14天內）
        if (isset($orderData['order_type']) && $orderData['order_type'] === '台北長照') {
            $this->validateTaipeiLongTermCareDates($dates);
        }

        // 檢查重複訂單
        $conflicts = $this->checkDuplicateOrders($orderData['customer_id'], $dates, $orderData['ride_time']);

        if (! empty($conflicts)) {
            throw new \Exception('存在重複訂單：'.implode(', ', $conflicts));
        }

        // 生成批次 ID
        $batchId = 'batch_'.time().'_'.Str::random(8);

        return DB::transaction(function () use ($orderData, $dates, $batchId) {
            $createdOrders = [];
            $errors = [];

            // 分批處理以避免記憶體問題
            $batches = array_chunk($dates, 10);

            foreach ($batches as $batchIndex => $batch) {
                foreach ($batch as $index => $date) {
                    try {
                        $orderDataWithDate = array_merge($orderData, [
                            'ride_date' => $date,
                            'batch_id' => $batchId,
                            'batch_sequence' => ($batchIndex * 10) + $index + 1,
                        ]);

                        if (! empty($orderData['carpool_customer_id'])) {
                            // 共乘訂單
                            $result = $this->carpoolGroupService->createCarpoolGroup(
                                $orderData['customer_id'],
                                $orderData['carpool_customer_id'],
                                $orderDataWithDate
                            );
                            $createdOrders = array_merge($createdOrders, $result['orders']);
                        } else {
                            // 單人訂單
                            $order = $this->createSingleOrder($orderDataWithDate);
                            $createdOrders[] = $order;

                            // 處理回程訂單
                            if (! empty($orderData['back_time'])) {
                                $returnOrder = $this->createReturnOrder($orderDataWithDate, $order);
                                $createdOrders[] = $returnOrder;
                            }
                        }

                    } catch (\Exception $e) {
                        $errors[] = [
                            'date' => $date,
                            'error' => $e->getMessage(),
                        ];
                        Log::error('批量建立訂單失敗', [
                            'date' => $date,
                            'error' => $e->getMessage(),
                            'batch_id' => $batchId,
                        ]);
                    }
                }

                // 記憶體清理
                if (memory_get_usage() > 50 * 1024 * 1024) { // 50MB
                    gc_collect_cycles();
                }
            }

            return [
                'batch_id' => $batchId,
                'successful_orders' => $createdOrders,
                'failed_dates' => $errors,
                'total_created' => count($createdOrders),
                'total_failed' => count($errors),
            ];
        });
    }

    /**
     * 產生週期性日期（支援多星期幾複選）
     */
    public function generateRecurringDates($startDate, $endDate, $weekdays, $recurrenceType = 'weekly')
    {
        $dates = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // 驗證日期範圍
        if ($start->gt($end)) {
            throw new \Exception('開始日期不能晚於結束日期');
        }

        if ($start->diffInMonths($end) > 6) {
            throw new \Exception('日期範圍不能超過 6 個月');
        }

        // 驗證星期幾陣列
        if (empty($weekdays) || ! is_array($weekdays)) {
            throw new \Exception('請至少選擇一個星期幾');
        }

        foreach ($weekdays as $weekday) {
            if (! is_numeric($weekday) || $weekday < 0 || $weekday > 6) {
                throw new \Exception('星期幾數值必須在 0-6 之間');
            }
        }

        // 找到開始日期所在週的週一
        $currentWeekStart = $start->copy()->startOfWeek();

        while ($currentWeekStart->lte($end)) {
            foreach ($weekdays as $weekday) {
                $targetDate = $currentWeekStart->copy();

                // 統一星期幾映射：0=週日, 1=週一, ..., 6=週六
                // 轉換為相對於週一的天數偏移
                if ($weekday == 0) {
                    // 週日是週一後6天
                    $targetDate->addDays(6);
                } else {
                    // 週一是0天偏移，週二是1天，依此類推
                    $targetDate->addDays($weekday - 1);
                }

                // 檢查是否在指定範圍內
                if ($targetDate->gte($start) && $targetDate->lte($end)) {
                    $dates[] = $targetDate->format('Y-m-d');
                }
            }

            // 移動到下一個週期
            switch ($recurrenceType) {
                case 'weekly':
                    $currentWeekStart->addWeek();
                    break;
                case 'biweekly':
                    $currentWeekStart->addWeeks(2);
                    break;
                case 'monthly':
                    $currentWeekStart->addMonth();
                    break;
                default:
                    throw new \Exception('無效的重複週期類型');
            }
        }

        // 排序並去重
        $dates = array_unique($dates);
        sort($dates);

        // 檢查數量限制
        if (count($dates) > 50) {
            throw new \Exception('生成的日期數量 ('.count($dates).') 超過 50 個限制，請調整日期範圍或週期');
        }

        if (empty($dates)) {
            throw new \Exception('根據設定條件未產生任何日期，請檢查設定');
        }

        return $dates;
    }

    /**
     * 驗證日期陣列
     */
    private function validateDates($dates)
    {
        if (empty($dates)) {
            throw new \Exception('請選擇至少一個日期');
        }

        if (count($dates) > 50) {
            throw new \Exception('單次最多只能建立 50 筆訂單');
        }

        foreach ($dates as $date) {
            $parsedDate = Carbon::parse($date);

            // 檢查日期不能是過去
            if ($parsedDate->isPast()) {
                throw new \Exception("日期 {$date} 不能是過去的日期");
            }

            // 檢查日期不能超過 6 個月
            if ($parsedDate->diffInMonths(Carbon::now()) > 6) {
                throw new \Exception("日期 {$date} 超過 6 個月限制");
            }
        }
    }

    /**
     * 驗證台北長照訂單日期限制（14天內）
     */
    private function validateTaipeiLongTermCareDates($dates)
    {
        $maxDate = Carbon::today()->addDays(14)->endOfDay();

        foreach ($dates as $dateString) {
            $date = Carbon::parse($dateString);
            if ($date->greaterThan($maxDate)) {
                throw new \Exception(
                    '台北長照訂單的用車日期僅能建立 14 天內（含今天）。'.
                    '無效日期：'.$date->format('Y-m-d')
                );
            }
        }
    }

    /**
     * 檢查重複訂單
     */
    private function checkDuplicateOrders($customerId, $dates, $time)
    {
        $conflicts = [];

        $existingOrders = Order::where('customer_id', $customerId)
            ->where('ride_time', $time)
            ->whereIn('ride_date', $dates)
            ->pluck('ride_date')
            ->toArray();

        foreach ($existingOrders as $conflictDate) {
            $conflicts[] = Carbon::parse($conflictDate)->format('Y-m-d');
        }

        return $conflicts;
    }

    /**
     * 建立單人訂單
     */
    private function createSingleOrder($orderData)
    {
        // 生成訂單編號
        $customer = Customer::findOrFail($orderData['customer_id']);
        $orderNumber = $this->orderNumberService->generateOrderNumber(
            $orderData['order_type'] ?? $customer->county_care ?? '一般長照',
            $customer->id_number
        );

        $preparedData = array_merge($this->prepareOrderData($orderData), [
            'order_number' => $orderNumber,
        ]);

        return Order::create($preparedData);
    }

    /**
     * 建立回程訂單
     */
    private function createReturnOrder($orderData, $outboundOrder)
    {
        // 生成回程訂單編號
        $customer = Customer::findOrFail($orderData['customer_id']);
        $returnOrderNumber = $this->orderNumberService->generateOrderNumber(
            $orderData['order_type'] ?? $customer->county_care ?? '一般長照',
            $customer->id_number
        );

        $returnData = array_merge($orderData, [
            'order_number' => $returnOrderNumber,
            'ride_time' => $orderData['back_time'],
            'pickup_address' => $orderData['dropoff_address'],
            'dropoff_address' => $orderData['pickup_address'],
            'pickup_county' => $orderData['dropoff_county'] ?? null,
            'pickup_district' => $orderData['dropoff_district'] ?? null,
            'pickup_lat' => $orderData['dropoff_lat'] ?? null,
            'pickup_lng' => $orderData['dropoff_lng'] ?? null,
            'dropoff_county' => $orderData['pickup_county'] ?? null,
            'dropoff_district' => $orderData['pickup_district'] ?? null,
            'dropoff_lat' => $orderData['pickup_lat'] ?? null,
            'dropoff_lng' => $orderData['pickup_lng'] ?? null,
        ]);

        return Order::create($this->prepareOrderData($returnData));
    }

    /**
     * 準備訂單資料
     */
    private function prepareOrderData($orderData)
    {
        // 確保資料格式正確，與現有的 OrderController 邏輯保持一致
        return [
            'order_number' => $orderData['order_number'] ?? null,
            'customer_id' => $orderData['customer_id'],
            'customer_name' => $orderData['customer_name'],
            'customer_id_number' => $orderData['customer_id_number'],
            'customer_phone' => $orderData['customer_phone'],
            'order_type' => $orderData['order_type'] ?? null,
            'service_company' => $orderData['service_company'] ?? null,
            'ride_date' => $orderData['ride_date'],
            'ride_time' => $orderData['ride_time'],
            'pickup_county' => $orderData['pickup_county'] ?? null,
            'pickup_district' => $orderData['pickup_district'] ?? null,
            'pickup_address' => $orderData['pickup_address'],
            'pickup_lat' => $orderData['pickup_lat'] ?? null,
            'pickup_lng' => $orderData['pickup_lng'] ?? null,
            'dropoff_county' => $orderData['dropoff_county'] ?? null,
            'dropoff_district' => $orderData['dropoff_district'] ?? null,
            'dropoff_address' => $orderData['dropoff_address'],
            'dropoff_lat' => $orderData['dropoff_lat'] ?? null,
            'dropoff_lng' => $orderData['dropoff_lng'] ?? null,
            'wheelchair' => $orderData['wheelchair'] ?? '否',
            'stair_machine' => $orderData['stair_machine'] ?? '否',
            'companions' => (int) ($orderData['companions'] ?? 0),
            'carpool_customer_id' => $orderData['carpool_customer_id'] ?? null,
            'carpool_name' => $orderData['carpool_name'] ?? null,
            'carpool_id' => $orderData['carpool_id'] ?? null,
            'remark' => $orderData['remark'] ?? null,
            'created_by' => auth()->user()->name ?? 'system',
            'identity' => $orderData['identity'] ?? null,
            'special_status' => $orderData['special_status'] ?? null,
            'status' => 'open',
            'batch_id' => $orderData['batch_id'] ?? null,
            'batch_sequence' => $orderData['batch_sequence'] ?? null,
        ];
    }
}
