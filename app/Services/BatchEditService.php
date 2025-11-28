<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 批量編輯訂單服務
 *
 * 提供訂單批量編輯功能，支援：
 * - 選擇性欄位更新（只更新填入的欄位）
 * - 共乘群組自動同步
 * - 地址驗證
 * - 交易完整性保證
 */
class BatchEditService
{
    /**
     * 允許批量編輯的欄位白名單
     */
    private const ALLOWED_FIELDS = [
        'ride_time',
        'pickup_address',
        'dropoff_address',
        'remark',
        'status',
        'special_status',
        'customer_phone',
        'wheelchair',
        'stair_machine',
    ];

    /**
     * 地址驗證正則表達式
     * 格式：縣市 + 區域 + 詳細地址
     */
    private const ADDRESS_PATTERN = '/^(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮).+$/u';

    /**
     * 批量更新訂單
     *
     * @param array $orderIds 訂單 ID 陣列
     * @param array $updateData 要更新的資料
     * @return array 包含 success, updated_count, affected_orders, errors 的結果陣列
     */
    public function batchUpdate(array $orderIds, array $updateData): array
    {
        // 驗證輸入
        if (empty($orderIds)) {
            return [
                'success' => false,
                'message' => '未選擇任何訂單',
                'updated_count' => 0,
                'affected_orders' => [],
                'errors' => ['未選擇任何訂單'],
            ];
        }

        // 過濾只包含允許的欄位
        $filteredData = $this->filterAllowedFields($updateData);

        if (empty($filteredData)) {
            return [
                'success' => false,
                'message' => '沒有提供任何要更新的欄位',
                'updated_count' => 0,
                'affected_orders' => [],
                'errors' => ['沒有提供任何要更新的欄位'],
            ];
        }

        // 驗證地址格式（如果有更新地址欄位）
        $addressValidationErrors = $this->validateAddresses($filteredData);
        if (!empty($addressValidationErrors)) {
            return [
                'success' => false,
                'message' => '地址格式驗證失敗',
                'updated_count' => 0,
                'affected_orders' => [],
                'errors' => $addressValidationErrors,
            ];
        }

        try {
            return DB::transaction(function () use ($orderIds, $filteredData) {
                $affectedOrders = [];
                $errors = [];
                $updatedCount = 0;

                // 載入所有要更新的訂單
                $orders = Order::whereIn('id', $orderIds)->get();

                if ($orders->isEmpty()) {
                    throw new Exception('找不到指定的訂單');
                }

                // 分組：依共乘群組 ID 分類
                $carpoolGroups = [];
                $individualOrders = [];

                foreach ($orders as $order) {
                    if ($order->carpool_group_id && !$order->is_group_dissolved) {
                        // 共乘訂單
                        $groupKey = $order->carpool_group_id;
                        if (!isset($carpoolGroups[$groupKey])) {
                            $carpoolGroups[$groupKey] = [];
                        }
                        $carpoolGroups[$groupKey][] = $order;
                    } else {
                        // 獨立訂單
                        $individualOrders[] = $order;
                    }
                }

                // 處理獨立訂單
                foreach ($individualOrders as $order) {
                    try {
                        $order->update($filteredData);
                        $affectedOrders[] = $order->order_number;
                        $updatedCount++;
                    } catch (Exception $e) {
                        $errors[] = "訂單 {$order->order_number} 更新失敗: {$e->getMessage()}";
                        Log::error("BatchEdit: 訂單更新失敗", [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // 處理共乘群組訂單
                foreach ($carpoolGroups as $groupId => $groupOrders) {
                    try {
                        // 更新群組內所有訂單
                        $allGroupOrders = Order::where('carpool_group_id', $groupId)
                            ->where('is_group_dissolved', false)
                            ->get();

                        foreach ($allGroupOrders as $order) {
                            $order->update($filteredData);
                            $affectedOrders[] = $order->order_number;
                            $updatedCount++;
                        }
                    } catch (Exception $e) {
                        $groupOrderNumbers = collect($groupOrders)->pluck('order_number')->implode(', ');
                        $errors[] = "共乘群組 {$groupId} (訂單: {$groupOrderNumbers}) 更新失敗: {$e->getMessage()}";
                        Log::error("BatchEdit: 共乘群組更新失敗", [
                            'carpool_group_id' => $groupId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // 記錄操作日誌
                Log::info("BatchEdit: 批量更新完成", [
                    'updated_count' => $updatedCount,
                    'requested_count' => count($orderIds),
                    'affected_orders' => $affectedOrders,
                    'update_data' => $filteredData,
                ]);

                return [
                    'success' => true,
                    'message' => "成功更新 {$updatedCount} 筆訂單",
                    'updated_count' => $updatedCount,
                    'affected_orders' => $affectedOrders,
                    'errors' => $errors,
                ];
            });
        } catch (Exception $e) {
            Log::error("BatchEdit: 批量更新失敗", [
                'order_ids' => $orderIds,
                'update_data' => $filteredData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '批量更新失敗: ' . $e->getMessage(),
                'updated_count' => 0,
                'affected_orders' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * 過濾只包含允許的欄位
     *
     * @param array $data 原始資料
     * @return array 過濾後的資料
     */
    private function filterAllowedFields(array $data): array
    {
        $filtered = [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }

        return $filtered;
    }

    /**
     * 驗證地址格式
     *
     * @param array $data 要驗證的資料
     * @return array 驗證錯誤訊息陣列
     */
    private function validateAddresses(array $data): array
    {
        $errors = [];

        // 驗證上車地址
        if (isset($data['pickup_address']) && !empty($data['pickup_address'])) {
            if (!preg_match(self::ADDRESS_PATTERN, $data['pickup_address'])) {
                $errors[] = '上車地址格式不正確，應包含：縣市 + 區域 + 詳細地址';
            }
        }

        // 驗證下車地址
        if (isset($data['dropoff_address']) && !empty($data['dropoff_address'])) {
            if (!preg_match(self::ADDRESS_PATTERN, $data['dropoff_address'])) {
                $errors[] = '下車地址格式不正確，應包含：縣市 + 區域 + 詳細地址';
            }
        }

        return $errors;
    }

    /**
     * 取得允許的欄位列表
     *
     * @return array
     */
    public static function getAllowedFields(): array
    {
        return self::ALLOWED_FIELDS;
    }

    /**
     * 檢查欄位是否允許編輯
     *
     * @param string $field 欄位名稱
     * @return bool
     */
    public static function isFieldAllowed(string $field): bool
    {
        return in_array($field, self::ALLOWED_FIELDS);
    }
}
