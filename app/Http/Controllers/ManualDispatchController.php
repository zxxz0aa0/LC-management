<?php

namespace App\Http\Controllers;

use App\Models\DispatchRecord;
use App\Models\Driver;
use App\Models\Order;
use App\Services\CarpoolGroupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualDispatchController extends Controller
{
    protected $carpoolService;

    public function __construct(CarpoolGroupService $carpoolService)
    {
        $this->carpoolService = $carpoolService;
    }

    /**
     * 顯示人工排趟管理頁面
     */
    public function index(Request $request)
    {
        // 從 Session 取得排趟列表（待指派訂單）
        $dispatchOrders = collect(session('dispatch_orders', []));

        // 初始化空集合
        $availableOrders = collect();
        $assignedOrders = collect(); // 已指派訂單
        $searchFleetNumber = null;

        // 只有在有搜尋條件時才查詢（需求 2）
        $hasSearchCriteria = $request->hasAny(['start_date', 'end_date', 'keyword', 'fleet_number']);

        if ($hasSearchCriteria) {
            // === 待派遣訂單查詢 ===
            $query = Order::where('status', 'open')
                ->where('is_main_order', true)  // 只顯示主訂單
                ->with(['customer', 'groupMembers']);

            // 日期範圍篩選
            if ($request->filled('start_date')) {
                $query->where('ride_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('ride_date', '<=', $request->end_date);
            }

            // 關鍵字搜尋（訂單編號、訂單類型、地址）
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('order_number', 'like', "%{$keyword}%")
                        ->orWhere('order_type', 'like', "%{$keyword}%")
                        ->orWhere('pickup_address', 'like', "%{$keyword}%")
                        ->orWhere('dropoff_address', 'like', "%{$keyword}%");
                });
            }

            // 排除已在排趟列表中的訂單
            $dispatchOrderIds = $dispatchOrders->pluck('id')->toArray();
            if (! empty($dispatchOrderIds)) {
                $query->whereNotIn('id', $dispatchOrderIds);
            }

            $availableOrders = $query->orderBy('ride_date', 'asc')
                ->orderBy('ride_time', 'asc')
                ->get();

            // === 隊員訂單查詢（需求 1）===
            if ($request->filled('fleet_number')) {
                $searchFleetNumber = $request->fleet_number;

                $assignedQuery = Order::whereIn('status', ['assigned', 'bkorder'])
                    ->where('driver_fleet_number', $searchFleetNumber)
                    ->where('is_main_order', true);

                // 套用日期範圍篩選（與待派遣訂單相同邏輯）
                if ($request->filled('start_date')) {
                    $assignedQuery->where('ride_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $assignedQuery->where('ride_date', '<=', $request->end_date);
                }

                $assignedOrders = $assignedQuery->with(['customer', 'groupMembers'])
                    ->orderBy('ride_date', 'asc')
                    ->orderBy('ride_time', 'asc')
                    ->get()
                    ->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'date' => $order->ride_date,
                            'time' => $order->ride_time,
                            'name' => $order->customer_name,
                            'origin_area' => $order->pickup_district,
                            'origin_address' => $order->pickup_address,
                            'dest_area' => $order->dropoff_district,
                            'dest_address' => $order->dropoff_address,
                            'type' => $order->order_type,
                            'special_status' => $order->special_status,
                            'is_assigned' => true, // 標記為已指派
                            'fleet_number' => $order->driver_fleet_number,
                        ];
                    });
            }
        }

        // 伺服器當前日期（用於快速日期按鈕）
        $serverCurrentDate = Carbon::now()->toDateString();

        return view('manual-dispatch.index', compact(
            'dispatchOrders',
            'availableOrders',
            'assignedOrders',
            'searchFleetNumber',
            'serverCurrentDate'
        ));
    }

    /**
     * 加入訂單到排趟列表
     */
    public function addToDispatch(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with(['customer', 'groupMembers'])->findOrFail($request->order_id);

        // 檢查訂單狀態
        if ($order->status !== 'open') {
            return response()->json([
                'status' => 'error',
                'message' => '此訂單狀態不是「可派遣」，無法加入排趟列表',
            ], 400);
        }

        // 從 Session 取得排趟列表
        $dispatchOrders = collect(session('dispatch_orders', []));

        // 檢查是否已存在
        if ($dispatchOrders->contains('id', $order->id)) {
            return response()->json([
                'status' => 'error',
                'message' => '此訂單已在排趟列表中',
            ], 400);
        }

        // 新增到排趟列表
        $dispatchOrders->push([
            'id' => $order->id,
            'date' => $order->ride_date,
            'time' => $order->ride_time,
            'name' => $order->customer_name,
            'origin_area' => $order->pickup_district,
            'origin_address' => $order->pickup_address,
            'dest_area' => $order->dropoff_district,
            'dest_address' => $order->dropoff_address,
            'type' => $order->order_type,
            'special_status' => $order->special_status,
        ]);

        // 儲存到 Session
        session(['dispatch_orders' => $dispatchOrders->toArray()]);

        return response()->json([
            'status' => 'success',
            'message' => '已加入排趟列表',
            'order' => $dispatchOrders->last(),
        ]);
    }

    /**
     * 從排趟列表移除訂單
     */
    public function removeFromDispatch(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        // 從 Session 取得排趟列表
        $dispatchOrders = collect(session('dispatch_orders', []));

        // 移除指定訂單
        $dispatchOrders = $dispatchOrders->reject(function ($order) use ($request) {
            return $order['id'] == $request->order_id;
        })->values();

        // 更新 Session
        session(['dispatch_orders' => $dispatchOrders->toArray()]);

        return response()->json([
            'status' => 'success',
            'message' => '已從排趟列表移除',
        ]);
    }

    /**
     * 清空排趟列表
     */
    public function clearDispatch(Request $request)
    {
        session()->forget('dispatch_orders');

        return response()->json([
            'status' => 'success',
            'message' => '排趟列表已清空',
        ]);
    }

    /**
     * 批次指派隊員編號
     */
    public function batchAssign(Request $request)
    {
        $request->validate([
            'fleet_number' => 'required|string',
        ]);

        // 從 Session 取得排趟列表（只有待指派的訂單）
        $dispatchOrders = collect(session('dispatch_orders', []));

        if ($dispatchOrders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => '排趟列表沒有待指派的訂單',
            ], 400);
        }

        // 根據隊編查找司機
        $driver = Driver::where('fleet_number', $request->fleet_number)->first();

        if (! $driver) {
            return response()->json([
                'status' => 'error',
                'message' => '查無此隊編：'.$request->fleet_number,
            ], 404);
        }

        try {
            $dispatchRecord = null;

            DB::transaction(function () use ($dispatchOrders, $driver, &$dispatchRecord) {
                $orderIds = $dispatchOrders->pluck('id')->toArray();

                // 只取得待指派的訂單（status='open'）
                $orders = Order::whereIn('id', $orderIds)
                    ->where('status', 'open')
                    ->where('is_main_order', true)
                    ->get();

                // === 建立排趟記錄 ===
                $batchId = (string) Str::uuid();
                $now = now();
                $userName = auth()->user()->name ?? '系統';

                // 產生排趟名稱：日期 時間 車隊編號 使用者名稱
                $dispatchName = sprintf(
                    '%s %s %s %s',
                    $now->format('Y-m-d'),
                    $now->format('H:i'),
                    $driver->fleet_number ?? 'N/A',
                    $userName
                );

                $dispatchRecord = DispatchRecord::create([
                    'batch_id' => $batchId,
                    'dispatch_name' => $dispatchName,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                    'driver_fleet_number' => $driver->fleet_number,
                    'order_ids' => $orderIds,
                    'order_count' => count($orderIds),
                    'order_details' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'ride_date' => $order->ride_date->format('Y-m-d'),
                            'ride_time' => $order->ride_time,
                            'pickup_address' => $order->pickup_address,
                            'dropoff_address' => $order->dropoff_address,
                            'order_type' => $order->order_type,
                        ];
                    })->toArray(),
                    'dispatch_date' => $orders->min('ride_date'),
                    'performed_by' => auth()->id(),
                    'performed_at' => $now,
                ]);

                // === 指派訂單並記錄 dispatch_record_id ===
                foreach ($orders as $order) {
                    // 檢查是否為共乘訂單
                    if ($order->carpool_group_id) {
                        // 使用 CarpoolGroupService 批量指派
                        $this->carpoolService->assignDriverToGroup(
                            $order->carpool_group_id,
                            $driver->id,
                            ['dispatch_record_id' => $dispatchRecord->id]
                        );
                    } else {
                        // 單筆訂單指派
                        $order->update([
                            'driver_id' => $driver->id,
                            'driver_name' => $driver->name,
                            'driver_fleet_number' => $driver->fleet_number ?? null,
                            'driver_plate_number' => $driver->plate_number ?? null,
                            'status' => 'assigned',
                            'updated_by' => auth()->id(),
                            'dispatch_record_id' => $dispatchRecord->id,
                        ]);
                    }
                }
            });

            // 清空排趟列表
            session()->forget('dispatch_orders');

            return response()->json([
                'status' => 'success',
                'message' => "✓ 排趟成功！已指派 {$dispatchOrders->count()} 筆訂單給隊員 {$driver->name} ({$request->fleet_number})",
                'dispatch_record_id' => $dispatchRecord->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '批次指派失敗：'.$e->getMessage(),
            ], 500);
        }
    }
}
