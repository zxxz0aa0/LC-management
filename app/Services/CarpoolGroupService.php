<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CarpoolGroupService
{
    private OrderNumberService $orderNumberService;

    public function __construct(OrderNumberService $orderNumberService)
    {
        $this->orderNumberService = $orderNumberService;
    }

    /**
     * 建立共乘群組
     */
    public function createCarpoolGroup($mainCustomerId, $carpoolCustomerId, $orderData)
    {
        return DB::transaction(function () use ($mainCustomerId, $carpoolCustomerId, $orderData) {
            // 為去程生成獨立的群組ID
            $outboundGroupId = 'carpool_'.Str::uuid()->toString();

            // 取得客戶資料
            $mainCustomer = Customer::findOrFail($mainCustomerId);
            $carpoolCustomer = Customer::findOrFail($carpoolCustomerId);

            // 生成訂單編號
            $orderNumbers = $this->generateCarpoolOrderNumbers($mainCustomer, $carpoolCustomer, $orderData);

            // 建立去程群組（主訂單 + 共乘成員）
            $mainOrder = $this->createMainOrder($mainCustomer, $orderData, $outboundGroupId, $orderNumbers['main'], $carpoolCustomer);
            $carpoolOrder = $this->createCarpoolMemberOrder($carpoolCustomer, $orderData, $outboundGroupId, $orderNumbers['carpool'], $orderNumbers['main'], $mainCustomer);

            $createdOrders = [$mainOrder, $carpoolOrder];
            $result = [
                'outbound_group_id' => $outboundGroupId,
                'return_group_id' => null,
                'orders' => $createdOrders,
                'total_orders' => count($createdOrders),
            ];

            // 處理回程訂單 - 建立獨立的回程群組
            if (! empty($orderData['back_time'])) {
                // 為回程生成獨立的群組ID
                $returnGroupId = 'carpool_'.Str::uuid()->toString();
                $returnOrders = $this->createReturnOrders($mainCustomer, $carpoolCustomer, $orderData, $returnGroupId, $orderNumbers);
                $createdOrders = array_merge($createdOrders, $returnOrders);

                $result['return_group_id'] = $returnGroupId;
                $result['orders'] = $createdOrders;
                $result['total_orders'] = count($createdOrders);
            }

            return $result;
        });
    }

    /**
     * 生成共乘訂單編號（使用原子化服務）
     */
    private function generateCarpoolOrderNumbers($mainCustomer, $carpoolCustomer, $orderData)
    {
        // 安全存取 order_type，使用客戶資料作為後備
        $orderType = $orderData['order_type'] ?? $mainCustomer->county_care ?? '一般長照';
        $mainCustomerIdNumber = $mainCustomer->id_number;
        $hasReturn = ! empty($orderData['back_time']);

        // 使用原子化服務生成所有編號
        return $this->orderNumberService->generateCarpoolOrderNumbers(
            $orderType,
            $mainCustomerIdNumber,
            $carpoolCustomer->id_number,
            $hasReturn
        );
    }

    /**
     * 建立主訂單
     */
    private function createMainOrder($customer, $orderData, $groupId, $orderNumber, $carpoolCustomer = null)
    {
        $carpoolData = [];
        if ($carpoolCustomer) {
            $carpoolData = [
                'special_status' => '共乘單',
                'carpool_customer_id' => $carpoolCustomer->id,
                'carpool_name' => $carpoolCustomer->name,
                'carpool_id' => $carpoolCustomer->id_number,
            ];
        }

        return Order::create(array_merge($this->prepareOrderData($customer, $orderData, $orderNumber), [
            'carpool_group_id' => $groupId,
            'is_main_order' => true,
            'carpool_member_count' => 2,
            'main_order_number' => $orderNumber,
            'member_sequence' => 1,
        ], $carpoolData));
    }

    /**
     * 建立共乘成員訂單
     */
    private function createCarpoolMemberOrder($customer, $orderData, $groupId, $orderNumber, $mainOrderNumber, $mainCustomer = null)
    {
        $carpoolData = [];
        if ($mainCustomer) {
            $carpoolData = [
                'special_status' => '共乘單',
                'carpool_customer_id' => $mainCustomer->id,
                'carpool_name' => $mainCustomer->name,
                'carpool_id' => $mainCustomer->id_number,
            ];
        }

        return Order::create(array_merge($this->prepareOrderData($customer, $orderData, $orderNumber), [
            'carpool_group_id' => $groupId,
            'is_main_order' => false,
            'carpool_member_count' => 2,
            'main_order_number' => $mainOrderNumber,
            'member_sequence' => 2,
        ], $carpoolData));
    }

    /**
     * 建立回程訂單（獨立群組）
     */
    private function createReturnOrders($mainCustomer, $carpoolCustomer, $orderData, $returnGroupId, $orderNumbers)
    {
        // 處理回程駕駛資訊：如果有填入回程駕駛，使用回程駕駛；否則留空
        $returnDriverData = [];
        if (! empty($orderData['return_driver_fleet_number']) || ! empty($orderData['return_driver_name'])) {
            $returnDriverData = [
                'driver_id' => $orderData['return_driver_id'] ?? null,
                'driver_name' => $orderData['return_driver_name'] ?? null,
                'driver_plate_number' => $orderData['return_driver_plate_number'] ?? null,
                'driver_fleet_number' => $orderData['return_driver_fleet_number'] ?? null,
            ];
        } else {
            // 回程駕駛資訊留空
            $returnDriverData = [
                'driver_id' => null,
                'driver_name' => null,
                'driver_plate_number' => null,
                'driver_fleet_number' => null,
            ];
        }

        // 準備回程訂單資料（地址對調）
        $returnOrderData = array_merge($orderData, [
            'ride_time' => $orderData['back_time'],
            'pickup_address' => $orderData['dropoff_address'],
            'pickup_county' => $orderData['dropoff_county'] ?? null,
            'pickup_district' => $orderData['dropoff_district'] ?? null,
            'dropoff_address' => $orderData['pickup_address'],
            'dropoff_county' => $orderData['pickup_county'] ?? null,
            'dropoff_district' => $orderData['pickup_district'] ?? null,
        ], $returnDriverData);

        // 建立主客戶回程訂單
        $returnMainOrder = Order::create(array_merge($this->prepareOrderData($mainCustomer, $returnOrderData, $orderNumbers['return_main']), [
            'carpool_group_id' => $returnGroupId,
            'is_main_order' => true,
            'carpool_member_count' => 2,
            'main_order_number' => $orderNumbers['return_main'],
            'member_sequence' => 1,
            // 共乘資訊：主訂單指向共乘客戶
            'special_status' => '共乘單',
            'carpool_customer_id' => $carpoolCustomer->id,
            'carpool_name' => $carpoolCustomer->name,
            'carpool_id' => $carpoolCustomer->id_number,
        ]));

        // 建立共乘客戶回程訂單
        $returnCarpoolOrder = Order::create(array_merge($this->prepareOrderData($carpoolCustomer, $returnOrderData, $orderNumbers['return_carpool']), [
            'carpool_group_id' => $returnGroupId,
            'is_main_order' => false,
            'carpool_member_count' => 2,
            'main_order_number' => $orderNumbers['return_main'],
            'member_sequence' => 2,
            // 共乘資訊：成員訂單指向主客戶
            'special_status' => '共乘單',
            'carpool_customer_id' => $mainCustomer->id,
            'carpool_name' => $mainCustomer->name,
            'carpool_id' => $mainCustomer->id_number,
        ]));

        return [$returnMainOrder, $returnCarpoolOrder];
    }

    /**
     * 準備訂單資料
     */
    private function prepareOrderData($customer, $orderData, $orderNumber)
    {
        // 解析地址
        $pickupAddress = $orderData['pickup_address'] ?? '';
        $dropoffAddress = $orderData['dropoff_address'] ?? '';

        // 拆出縣市區域
        preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $pickupAddress, $pickupMatches);
        preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $dropoffAddress, $dropoffMatches);

        return [
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_id_number' => $customer->id_number,
            'customer_phone' => is_array($customer->phone_number)
                ? $customer->phone_number[0]
                : $customer->phone_number,

            // 訂單基本資訊（使用安全存取）
            'order_type' => $orderData['order_type'] ?? null,
            'service_company' => $orderData['service_company'] ?? null,
            'ride_date' => $orderData['ride_date'],
            'ride_time' => $orderData['ride_time'],
            'status' => $orderData['status'] ?? 'open',

            // 地址資訊
            'pickup_address' => $pickupAddress,
            'pickup_county' => $orderData['pickup_county'] ?? $pickupMatches[1] ?? null,
            'pickup_district' => $orderData['pickup_district'] ?? $pickupMatches[2] ?? null,
            'pickup_lat' => $orderData['pickup_lat'] ?? null,
            'pickup_lng' => $orderData['pickup_lng'] ?? null,
            'dropoff_address' => $dropoffAddress,
            'dropoff_county' => $orderData['dropoff_county'] ?? $dropoffMatches[1] ?? null,
            'dropoff_district' => $orderData['dropoff_district'] ?? $dropoffMatches[2] ?? null,
            'dropoff_lat' => $orderData['dropoff_lat'] ?? null,
            'dropoff_lng' => $orderData['dropoff_lng'] ?? null,

            // 服務需求
            'wheelchair' => $orderData['wheelchair'] ?? '否',
            'stair_machine' => $orderData['stair_machine'] ?? '否',
            'companions' => (int) ($orderData['companions'] ?? 0),

            // 駕駛資訊
            'driver_id' => $orderData['driver_id'] ?? null,
            'driver_name' => $orderData['driver_name'] ?? null,
            'driver_plate_number' => $orderData['driver_plate_number'] ?? null,
            'driver_fleet_number' => $orderData['driver_fleet_number'] ?? null,

            // 其他資訊
            'remark' => $orderData['remark'] ?? null,
            'created_by' => $orderData['created_by'] ?? auth()->user()->name ?? 'system',
            'identity' => $orderData['identity'] ?? null,

            // 批量建立訂單的專用欄位
            'batch_id' => $orderData['batch_id'] ?? null,
            'batch_sequence' => $orderData['batch_sequence'] ?? null,
        ];
    }

    /**
     * 同步群組狀態
     */
    public function syncGroupStatus($groupId, $newStatus, $updateData = [])
    {
        DB::transaction(function () use ($groupId, $newStatus, $updateData) {
            Order::where('carpool_group_id', $groupId)
                ->where('is_group_dissolved', false)
                ->update(array_merge([
                    'status' => $newStatus,
                    'updated_at' => now(),
                    'updated_by' => auth()->id(), // 記錄更新人員
                ], $updateData));
        });

        Log::info('群組狀態同步', [
            'group_id' => $groupId,
            'new_status' => $newStatus,
            'update_data' => $updateData,
        ]);
    }

    /**
     * 指派司機給群組
     */
    public function assignDriverToGroup($groupId, $driverId, $additionalData = [])
    {
        $driver = Driver::findOrFail($driverId);

        $this->syncGroupStatus($groupId, 'assigned', array_merge([
            'driver_id' => $driverId,
            'driver_name' => $driver->name,
            'driver_fleet_number' => $driver->fleet_number ?? null,
            'driver_plate_number' => $driver->plate_number ?? null,
        ], $additionalData));

        return [
            'success' => true,
            'message' => "已成功指派司機 {$driver->name} 給群組",
            'driver' => $driver,
        ];
    }

    /**
     * 移除群組駕駛指派
     */
    public function unassignDriverFromGroup($groupId)
    {
        $this->syncGroupStatus($groupId, 'open', [
            'driver_id' => null,
            'driver_name' => null,
            'driver_fleet_number' => null,
            'driver_plate_number' => null,
        ]);

        return [
            'success' => true,
            'message' => '已成功移除群組駕駛指派',
        ];
    }

    /**
     * 取消群組訂單
     */
    public function cancelGroup($groupId, $reason = '')
    {
        $this->syncGroupStatus($groupId, 'cancelled', [
            'remark' => $reason ? "取消原因：{$reason}" : null,
        ]);

        return [
            'success' => true,
            'message' => '群組訂單已取消',
            'reason' => $reason,
        ];
    }

    /**
     * 解除共乘群組
     */
    public function dissolveGroup($groupId, $reason = '', $force = false)
    {
        return DB::transaction(function () use ($groupId, $reason, $force) {
            $groupOrders = Order::where('carpool_group_id', $groupId)->get();

            if ($groupOrders->isEmpty()) {
                throw new \Exception('群組不存在');
            }

            // 檢查解除條件
            $this->validateDissolutionConditions($groupOrders, $force);

            $result = ['orders' => [], 'message' => ''];

            foreach ($groupOrders as $order) {
                $dissolvedOrder = $this->dissolveOrder($order, $reason);
                $result['orders'][] = $dissolvedOrder;
            }

            $result['message'] = $this->generateDissolutionMessage($result['orders']);

            Log::info('群組解除', [
                'group_id' => $groupId,
                'reason' => $reason,
                'orders_count' => count($result['orders']),
            ]);

            return $result;
        });
    }

    /**
     * 解除單一訂單
     */
    private function dissolveOrder($order, $reason)
    {
        $originalStatus = $order->status;

        $updateData = [
            'original_group_id' => $order->carpool_group_id,
            'carpool_group_id' => null,
            'is_main_order' => true,
            'carpool_member_count' => 1,
            'main_order_number' => null,
            'member_sequence' => null,
            'is_group_dissolved' => true,
            'dissolved_at' => now(),
            'dissolved_by' => auth()->user()->name ?? 'system',
            'updated_by' => auth()->id(), // 記錄更新人員

            // 清除共乘資訊
            'carpool_customer_id' => null,
            'carpool_name' => null,
            'carpool_id' => null,
        ];

        // 根據原狀態決定解除後的處理
        if ($originalStatus === 'assigned' && ! $order->is_main_order) {
            // 非主訂單釋放司機，回到待派遣狀態
            $updateData = array_merge($updateData, [
                'driver_id' => null,
                'driver_name' => null,
                'driver_fleet_number' => null,
                'driver_plate_number' => null,
                'status' => 'open',
            ]);
        }

        $order->update($updateData);

        return [
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'order_number' => $order->order_number,
            'original_status' => $originalStatus,
            'new_status' => $order->status,
            'driver_retained' => $order->driver_id ? true : false,
        ];
    }

    /**
     * 驗證解除條件
     */
    private function validateDissolutionConditions($groupOrders, $force)
    {
        $inProgressOrders = $groupOrders->where('status', 'in_progress');

        if ($inProgressOrders->isNotEmpty() && ! $force) {
            throw new \Exception('群組中有正在進行的訂單，無法解除。如需強制解除，請聯繫管理員。');
        }

        $completedOrders = $groupOrders->whereIn('status', ['completed', 'cancelled']);

        if ($completedOrders->count() === $groupOrders->count()) {
            throw new \Exception('群組中所有訂單已完成或取消，無需解除。');
        }
    }

    /**
     * 生成解除訊息
     */
    private function generateDissolutionMessage($orders)
    {
        $customerNames = collect($orders)->pluck('customer_name')->unique()->join('、');
        $orderCount = count($orders);

        return "已成功解除共乘群組，涉及客戶：{$customerNames}，共 {$orderCount} 筆訂單";
    }

    /**
     * 取得群組資訊
     */
    public function getGroupInfo($groupId)
    {
        $orders = Order::where('carpool_group_id', $groupId)
            ->where('is_group_dissolved', false)
            ->orderBy('is_main_order', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        $mainOrder = $orders->where('is_main_order', true)->first();
        $members = $orders->where('is_main_order', false);

        return [
            'id' => $groupId,
            'member_count' => $orders->count(),
            'status' => $mainOrder->status,
            'created_at' => $mainOrder->created_at->format('Y-m-d H:i:s'),
            'main_order' => $mainOrder,
            'members' => $members->values(),
            'all_orders' => $orders->values(),
        ];
    }
}
