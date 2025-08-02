<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarpoolGroupService
{
    /**
     * 建立共乘群組
     */
    public function createCarpoolGroup($mainCustomerId, $carpoolCustomerId, $orderData)
    {
        return DB::transaction(function () use ($mainCustomerId, $carpoolCustomerId, $orderData) {
            // 生成群組ID
            $groupId = 'carpool_' . time() . '_' . rand(1000, 9999);
            
            // 取得客戶資料
            $mainCustomer = Customer::findOrFail($mainCustomerId);
            $carpoolCustomer = Customer::findOrFail($carpoolCustomerId);
            
            // 生成訂單編號
            $orderNumbers = $this->generateCarpoolOrderNumbers($mainCustomer, $carpoolCustomer, $orderData);
            
            // 建立主訂單
            $mainOrder = $this->createMainOrder($mainCustomer, $orderData, $groupId, $orderNumbers['main']);
            
            // 建立共乘成員訂單
            $carpoolOrder = $this->createCarpoolMemberOrder($carpoolCustomer, $orderData, $groupId, $orderNumbers['carpool'], $orderNumbers['main']);
            
            $createdOrders = [$mainOrder, $carpoolOrder];
            
            // 處理回程訂單
            if (!empty($orderData['back_time'])) {
                $returnOrders = $this->createReturnOrders($mainCustomer, $carpoolCustomer, $orderData, $groupId, $orderNumbers);
                $createdOrders = array_merge($createdOrders, $returnOrders);
            }
            
            return [
                'group_id' => $groupId,
                'orders' => $createdOrders,
                'total_orders' => count($createdOrders)
            ];
        });
    }
    
    /**
     * 生成共乘訂單編號
     */
    private function generateCarpoolOrderNumbers($mainCustomer, $carpoolCustomer, $orderData)
    {
        $typeCodeMap = [
            '新北長照' => 'NTPC',
            '台北長照' => 'TPC',
            '新北復康' => 'NTFK',
            '愛接送' => 'LT',
        ];
        
        $today = Carbon::now();
        $typeCode = $typeCodeMap[$orderData['order_type']] ?? 'UNK';
        $date = $today->format('Ymd');
        $time = $today->format('Hi');
        
        // 主訂單編號
        $mainIdSuffix = substr($mainCustomer->id_number, -3);
        $mainSerial = str_pad(Order::whereDate('created_at', $today->toDateString())->count() + 1, 4, '0', STR_PAD_LEFT);
        $mainOrderNumber = $typeCode . $mainIdSuffix . $date . $time . $mainSerial;
        
        // 共乘成員編號（基於主訂單編號）
        $carpoolOrderNumber = $mainOrderNumber . '-M2';
        
        $orderNumbers = [
            'main' => $mainOrderNumber,
            'carpool' => $carpoolOrderNumber
        ];
        
        // 如果有回程，生成回程編號
        if (!empty($orderData['back_time'])) {
            $returnSerial = str_pad(Order::whereDate('created_at', $today->toDateString())->count() + 2, 4, '0', STR_PAD_LEFT);
            $returnMainNumber = $typeCode . $mainIdSuffix . $date . $time . $returnSerial;
            $returnCarpoolNumber = $returnMainNumber . '-M2';
            
            $orderNumbers['return_main'] = $returnMainNumber;
            $orderNumbers['return_carpool'] = $returnCarpoolNumber;
        }
        
        return $orderNumbers;
    }
    
    /**
     * 建立主訂單
     */
    private function createMainOrder($customer, $orderData, $groupId, $orderNumber)
    {
        return Order::create(array_merge($this->prepareOrderData($customer, $orderData, $orderNumber), [
            'carpool_group_id' => $groupId,
            'is_main_order' => true,
            'carpool_member_count' => 2,
            'main_order_number' => $orderNumber,
            'member_sequence' => 1,
        ]));
    }
    
    /**
     * 建立共乘成員訂單
     */
    private function createCarpoolMemberOrder($customer, $orderData, $groupId, $orderNumber, $mainOrderNumber)
    {
        return Order::create(array_merge($this->prepareOrderData($customer, $orderData, $orderNumber), [
            'carpool_group_id' => $groupId,
            'is_main_order' => false,
            'carpool_member_count' => 2,
            'main_order_number' => $mainOrderNumber,
            'member_sequence' => 2,
        ]));
    }
    
    /**
     * 建立回程訂單
     */
    private function createReturnOrders($mainCustomer, $carpoolCustomer, $orderData, $groupId, $orderNumbers)
    {
        // 準備回程訂單資料（地址對調）
        $returnOrderData = array_merge($orderData, [
            'ride_time' => $orderData['back_time'],
            'pickup_address' => $orderData['dropoff_address'],
            'pickup_county' => $orderData['dropoff_county'] ?? null,
            'pickup_district' => $orderData['dropoff_district'] ?? null,
            'dropoff_address' => $orderData['pickup_address'],
            'dropoff_county' => $orderData['pickup_county'] ?? null,
            'dropoff_district' => $orderData['pickup_district'] ?? null,
        ]);
        
        // 建立主客戶回程訂單
        $returnMainOrder = Order::create(array_merge($this->prepareOrderData($mainCustomer, $returnOrderData, $orderNumbers['return_main']), [
            'carpool_group_id' => $groupId,
            'is_main_order' => true,
            'carpool_member_count' => 2,
            'main_order_number' => $orderNumbers['return_main'],
            'member_sequence' => 1,
        ]));
        
        // 建立共乘客戶回程訂單
        $returnCarpoolOrder = Order::create(array_merge($this->prepareOrderData($carpoolCustomer, $returnOrderData, $orderNumbers['return_carpool']), [
            'carpool_group_id' => $groupId,
            'is_main_order' => false,
            'carpool_member_count' => 2,
            'main_order_number' => $orderNumbers['return_main'],
            'member_sequence' => 2,
        ]));
        
        return [$returnMainOrder, $returnCarpoolOrder];
    }
    
    /**
     * 準備訂單資料
     */
    private function prepareOrderData($customer, $orderData, $orderNumber)
    {
        // 解析地址
        $pickupAddress = $orderData['pickup_address'];
        $dropoffAddress = $orderData['dropoff_address'];
        
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
            
            // 訂單基本資訊
            'order_type' => $orderData['order_type'],
            'service_company' => $orderData['service_company'],
            'ride_date' => $orderData['ride_date'],
            'ride_time' => $orderData['ride_time'],
            'status' => $orderData['status'] ?? 'open',
            
            // 地址資訊
            'pickup_address' => $pickupAddress,
            'pickup_county' => $pickupMatches[1] ?? null,
            'pickup_district' => $pickupMatches[2] ?? null,
            'dropoff_address' => $dropoffAddress,
            'dropoff_county' => $dropoffMatches[1] ?? null,
            'dropoff_district' => $dropoffMatches[2] ?? null,
            
            // 服務需求
            'wheelchair' => $orderData['wheelchair'] ?? false,
            'stair_machine' => $orderData['stair_machine'] ?? false,
            'companions' => $orderData['companions'] ?? 0,
            
            // 其他資訊
            'remark' => $orderData['remark'] ?? null,
            'created_by' => $orderData['created_by'] ?? auth()->user()->name,
            'identity' => $orderData['identity'] ?? null,
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
                     'updated_at' => now()
                 ], $updateData));
        });
        
        Log::info('群組狀態同步', [
            'group_id' => $groupId,
            'new_status' => $newStatus,
            'update_data' => $updateData
        ]);
    }
    
    /**
     * 指派司機給群組
     */
    public function assignDriverToGroup($groupId, $driverId)
    {
        $driver = Driver::findOrFail($driverId);
        
        $this->syncGroupStatus($groupId, 'assigned', [
            'driver_id' => $driverId,
            'driver_name' => $driver->name,
            'driver_fleet_number' => $driver->fleet_number ?? null,
            'driver_plate_number' => $driver->plate_number ?? null,
        ]);
        
        return [
            'success' => true,
            'message' => "已成功指派司機 {$driver->name} 給群組",
            'driver' => $driver
        ];
    }
    
    /**
     * 取消群組訂單
     */
    public function cancelGroup($groupId, $reason = '')
    {
        $this->syncGroupStatus($groupId, 'cancelled', [
            'remark' => $reason ? "取消原因：{$reason}" : null
        ]);
        
        return [
            'success' => true,
            'message' => '群組訂單已取消',
            'reason' => $reason
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
                'orders_count' => count($result['orders'])
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
            
            // 清除共乘資訊
            'carpool_customer_id' => null,
            'carpool_name' => null,
            'carpool_id' => null,
        ];
        
        // 根據原狀態決定解除後的處理
        if ($originalStatus === 'assigned' && !$order->is_main_order) {
            // 非主訂單釋放司機，回到待派遣狀態
            $updateData = array_merge($updateData, [
                'driver_id' => null,
                'driver_name' => null,
                'driver_fleet_number' => null,
                'driver_plate_number' => null,
                'status' => 'open'
            ]);
        }
        
        $order->update($updateData);
        
        return [
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'order_number' => $order->order_number,
            'original_status' => $originalStatus,
            'new_status' => $order->status,
            'driver_retained' => $order->driver_id ? true : false
        ];
    }
    
    /**
     * 驗證解除條件
     */
    private function validateDissolutionConditions($groupOrders, $force)
    {
        $inProgressOrders = $groupOrders->where('status', 'in_progress');
        
        if ($inProgressOrders->isNotEmpty() && !$force) {
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
            'members' => $members->values()->all(),
            'all_orders' => $orders->values()->all()
        ];
    }
}