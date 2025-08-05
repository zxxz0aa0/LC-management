<?php

namespace App\Http\Controllers;

use App\Exceptions\ConcurrencyException;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Landmark;
use App\Models\Order;
use App\Rules\UniqueOrderDateTime;
use App\Services\CarpoolGroupService;
use App\Services\OrderNumberService;
use App\Services\BatchOrderService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    protected $carpoolGroupService;
    protected $orderNumberService;
    protected $batchOrderService;
    
    public function __construct(CarpoolGroupService $carpoolGroupService, OrderNumberService $orderNumberService, BatchOrderService $batchOrderService)
    {
        $this->carpoolGroupService = $carpoolGroupService;
        $this->orderNumberService = $orderNumberService;
        $this->batchOrderService = $batchOrderService;
    }
    
    public function index(Request $request)
    {
        $query = Order::filter($request);

        // 如果沒有指定日期篩選，預設顯示今天的訂單
        if (! $request->filled('start_date') && ! $request->filled('end_date')) {
            $query->whereDate('ride_date', Carbon::today());
        }

        // 排序 & 分頁
        $orders = $query->latest()->paginate(50);

        // 如果你有客戶搜尋邏輯，要一起撈
        $customers = collect();
        if ($request->filled('customer_id')) {
            // 優先透過 ID 精確查找
            $customer = Customer::find($request->customer_id);
            if ($customer) {
                $customers->push($customer);
            }
        } elseif ($request->filled('keyword')) {
            // 其次透過關鍵字模糊搜尋
            $customers = Customer::where('name', 'like', '%'.$request->keyword.'%')
                ->orWhere('id_number', 'like', '%'.$request->keyword.'%')
                ->orWhere('phone_number', 'like', '%'.$request->keyword.'%')
                ->get();
        }

        return view('orders.index', compact('orders', 'customers'));
    }

    // 顯示新增訂單表單畫面
    public function create(Request $request)
    {
        $customer = null;

        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->input('customer_id'));
        }

        $user = auth()->user(); // 🔹目前登入的使用者

        // 保留搜尋參數，讓返回按鈕能維持搜尋狀態
        $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'customer_id']);

        if ($request->ajax()) {
            return view('orders.create', compact('customer', 'user', 'searchParams'));
        }

        return view('orders.create', compact('customer', 'user', 'searchParams'));
    }

    // 儲存新訂單資料（之後會補功能）

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_id_number' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:255',
                'customer_id' => 'required|integer',
                'ride_date' => 'required|date',
                'ride_time' => [
                    'required',
                    'date_format:H:i',
                    new UniqueOrderDateTime($request->customer_id, $request->ride_date, $request->back_time)
                ],
                'back_time' => 'nullable|date_format:H:i',
                'pickup_address' => [
                    'required',
                    'string',
                    'regex:/^(.+市|.+縣)(.+區|.+鄉|.+鎮).+$/u',
                ],
                'dropoff_address' => [
                    'required',
                    'string',
                    'regex:/^(.+市|.+縣)(.+區|.+鄉|.+鎮).+$/u',
                ],
                'status' => 'required|in:open,assigned,replacement,blocked,cancelled',
                'companions' => 'required|integer|min:0',
                'order_type' => 'required|string',
                'service_company' => 'required|string',
                'wheelchair' => 'required|string',
                'stair_machine' => 'required|string',
                
                // 共乘相關欄位
                'carpool_customer_id' => 'nullable|exists:customers,id',
                'remark' => 'nullable|string',
                'created_by' => 'required|string',
                'identity' => 'required|string',
                'carpool_name' => 'nullable|string',
                'special_status' => 'nullable|string',
                'carpool_customer_id' => 'nullable|integer',
                'carpool_id' => 'nullable|string',
                'driver_id' => 'nullable|integer',
                'driver_name' => 'nullable|string',
                'driver_plate_number' => 'nullable|string',
                'driver_fleet_number' => 'nullable|string',
                'carpoolSearchInput' => 'nullable|string',
                'carpool_id_number' => 'nullable|string',
            ], [
                'pickup_address.regex' => '上車地址必須包含「市/縣」與「區/鄉/鎮」',
                'dropoff_address.regex' => '下車地址必須包含「市/縣」與「區/鄉/鎮」',
                'back_time.date_format' => '回程時間格式錯誤，請使用 HH:MM 格式',
            ]
            );
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                $request->flash(); // 保留使用者輸入的資料

                return response()->json([
                    'html' => view('orders.components.order-form', [
                        'customer' => Customer::find($request->input('customer_id')),
                        'user' => auth()->user(),
                    ])->withErrors(new \Illuminate\Support\MessageBag($e->errors()))->render(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }

        // 拆解地址資訊
        $pickupAddress = $validated['pickup_address'];
        $dropoffAddress = $validated['dropoff_address'];
        
        preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $pickupAddress, $pickupMatches);
        $validated['pickup_county'] = $pickupMatches[1] ?? null;
        $validated['pickup_district'] = $pickupMatches[2] ?? null;
        
        preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $dropoffAddress, $dropoffMatches);
        $validated['dropoff_county'] = $dropoffMatches[1] ?? null;
        $validated['dropoff_district'] = $dropoffMatches[2] ?? null;

        // 檢查是否為共乘訂單
        $isCarpool = !empty($validated['carpool_customer_id']);
        
        if ($isCarpool) {
            // 建立共乘群組
            $result = $this->carpoolGroupService->createCarpoolGroup(
                $validated['customer_id'],
                $validated['carpool_customer_id'],
                $validated
            );
            
            $ordersCreated = $result['total_orders'];
            $order = $result['orders'][0]; // 主訂單
            
            $successMessage = $ordersCreated === 2 
                ? '成功建立 2 筆共乘訂單（去程）' 
                : "成功建立 {$ordersCreated} 筆訂單（共乘含去程回程）";
                
        } else {
            // 建立單人訂單（使用原有邏輯的簡化版）
            $order = $this->createSingleOrder($validated);
            $ordersCreated = 1;
            
            // 處理回程訂單
            if (!empty($validated['back_time'])) {
                $this->createReturnOrder($validated, $order);
                $ordersCreated = 2;
            }
            
            $successMessage = $ordersCreated === 2 
                ? '成功建立 2 筆訂單（去程和回程）' 
                : '訂單建立成功';
        }

        // 記錄地標使用次數
        $this->recordLandmarkUsage($request->get('pickup_address'), $request->get('pickup_landmark_id'));
        $this->recordLandmarkUsage($request->get('dropoff_address'), $request->get('dropoff_landmark_id'));

        if ($request->ajax()) {
            $query = Order::filter($request);
            $orders = $query->orderBy('ride_date', 'desc')->get();
            return view('orders.components.order-table', compact('orders'))->render();
        }

        // 頁面式提交，成功後返回訂單列表並保持完整搜尋條件
        $redirectParams = $this->prepareSearchParams($request, $order);

        return redirect()->route('orders.index', $redirectParams)->with('success', $successMessage);
    }

    /**
     * 批量建立訂單（支援三種日期模式）
     */
    public function storeBatch(Request $request)
    {
        try {
            // 基本驗證規則
            $rules = [
                'date_mode' => 'required|in:single,manual,recurring',
                
                // 基本訂單欄位驗證
                'customer_name' => 'required|string|max:255',
                'customer_id_number' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:255',
                'customer_id' => 'required|integer',
                'order_type' => 'required|string',
                'service_company' => 'required|string',
                'ride_time' => 'required|date_format:H:i',
                'back_time' => 'nullable|date_format:H:i',
                'pickup_address' => [
                    'required',
                    'string',
                    'regex:/^(.+市|.+縣)(.+區|.+鄉|.+鎮).+$/u',
                ],
                'dropoff_address' => [
                    'required',
                    'string',
                    'regex:/^(.+市|.+縣)(.+區|.+鄉|.+鎮).+$/u',
                ],
                'companions' => 'required|integer|min:0',
                'wheelchair' => 'nullable|boolean',
                'stair_machine' => 'nullable|boolean',
                'remark' => 'nullable|string',
                'carpool_customer_id' => 'nullable|integer',
                'carpool_name' => 'nullable|string',
                'carpool_id' => 'nullable|string',
            ];
            
            // 根據日期模式添加特定驗證規則
            if ($request->input('date_mode') === 'manual') {
                $rules['selected_dates'] = 'required|array|min:1|max:50';
                $rules['selected_dates.*'] = 'date|after:today';
            } elseif ($request->input('date_mode') === 'recurring') {
                $rules['start_date'] = 'required|date|after:today';
                $rules['end_date'] = 'required|date|after:start_date';
                $rules['weekdays'] = 'required|array|min:1|max:7';
                $rules['weekdays.*'] = 'integer|between:0,6';
                $rules['recurrence_type'] = 'required|in:weekly,biweekly,monthly';
            }
            
            $validated = $request->validate($rules);
            
            if ($validated['date_mode'] === 'single') {
                // 使用現有的單日建立邏輯
                return $this->store($request);
            }
            
            $dates = [];
            
            if ($validated['date_mode'] === 'manual') {
                // 手動多日模式
                $dates = $validated['selected_dates'];
            } elseif ($validated['date_mode'] === 'recurring') {
                // 週期性模式
                $dates = $this->batchOrderService->generateRecurringDates(
                    $validated['start_date'],
                    $validated['end_date'],
                    $validated['weekdays'],
                    $validated['recurrence_type']
                );
            }
            
            if (empty($dates)) {
                throw new \Exception('未選擇任何日期，請檢查設定');
            }
            
            // 解析地址中的縣市區域資訊
            $validated = $this->extractAddressInfo($validated);
            
            $result = $this->batchOrderService->createMultipleDaysOrders($validated, $dates);
            
            $message = "批量建立完成：成功 {$result['total_created']} 筆";
            if ($result['total_failed'] > 0) {
                $message .= "，失敗 {$result['total_failed']} 筆";
                
                // 如果有失敗的訂單，添加詳細錯誤信息
                $failedDates = array_column($result['failed_dates'], 'date');
                $message .= "（失敗日期：" . implode(', ', $failedDates) . "）";
            }
            
            // 記錄地標使用次數
            if (isset($validated['pickup_landmark_id'])) {
                $this->recordLandmarkUsage($validated['pickup_address'], $validated['pickup_landmark_id']);
            }
            if (isset($validated['dropoff_landmark_id'])) {
                $this->recordLandmarkUsage($validated['dropoff_address'], $validated['dropoff_landmark_id']);
            }
            
            // 保持搜尋參數（使用所有建立的訂單來設定搜尋範圍）
            $redirectParams = $this->prepareBatchSearchParams($request, $result['successful_orders']);
            
            return redirect()->route('orders.index', $redirectParams)->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('批量建立訂單失敗', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return back()->withErrors(['batch_error' => $e->getMessage()])->withInput();
        }
    }

    // 顯示單筆訂單資料（預留）
    public function show(Order $order)
    {
        $driver = null;
        if ($order->driver_id) {
            $driver = \App\Models\Driver::find($order->driver_id);
        }

        // 保留搜尋參數，讓返回按鈕能維持搜尋狀態
        $searchParams = request()->only(['keyword', 'start_date', 'end_date', 'customer_id']);

        return view('orders.show', compact('order', 'driver', 'searchParams'));
    }

    // 顯示編輯表單（預留）
    public function edit(Order $order)
    {
        // 保留搜尋參數，讓返回按鈕能維持搜尋狀態
        $searchParams = request()->only(['keyword', 'start_date', 'end_date', 'customer_id']);

        // 如果是AJAX
        if (request()->ajax()) {
            return view('orders.edit', [
                'order' => $order,
                'customer' => $order->customer,
                'user' => auth()->user(),
                'searchParams' => $searchParams,
            ]);
        }

        // 如果直接進頁面
        return view('orders.edit', compact('order', 'searchParams'));
    }

    // 更新訂單資料（預留）
    public function update(UpdateOrderRequest $request, Order $order)
    {
        try {
            $validated = $request->validated();

            // 將表單中的共乘與駕駛資訊欄位轉成資料表對應欄位
            $validated['carpool_name'] = $validated['carpool_with'] ?? null;
            $validated['carpool_id'] = $validated['carpool_id_number'] ?? null;

            $pickupAddress = $validated['pickup_address'];
            $dropoffAddress = $validated['dropoff_address'];

            // 拆出 pickup 地點
            preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $pickupAddress, $pickupMatches);
            $validated['pickup_county'] = $pickupMatches[1] ?? null;
            $validated['pickup_district'] = $pickupMatches[2] ?? null;

            // 拆出 dropoff 地點
            preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $dropoffAddress, $dropoffMatches);
            $validated['dropoff_county'] = $dropoffMatches[1] ?? null;
            $validated['dropoff_district'] = $dropoffMatches[2] ?? null;

            unset($validated['carpoolSearchInput'], $validated['carpool_id_number'], $validated['carpool_with']);

            // 記錄原始駕駛ID，用於檢測駕駛變更
            $originalDriverId = $order->driver_id;
            $newDriverId = $validated['driver_id'] ?? null;

            $order->update($validated);

            // 檢查共乘訂單的駕駛變更並同步群組
            if ($order->carpool_group_id) {
                $this->syncCarpoolGroupDriverChanges($order->carpool_group_id, $originalDriverId, $newDriverId);
            }

            if ($request->ajax()) {
                $query = Order::filter($request);

                $orders = $query->orderBy('ride_date', 'desc')->get();

                return view('orders.components.order-table', compact('orders'))->render();
            }

            return redirect()->route('orders.index')->with('success', '訂單更新成功');
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                $request->flash(); // 保留使用者輸入的資料

                return response()->json([
                    'html' => view('orders.components.order-form', [
                        'order' => $order, // 傳入 order 物件
                        'customer' => $order->customer,
                        'user' => auth()->user(),
                    ])->withErrors(new \Illuminate\Support\MessageBag($e->errors()))->render(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }

    }

    // 刪除訂單（預留）
    public function destroy(Order $order)
    {
        try {
            // 刪除訂單
            $order->delete();
            
            return response()->json([
                'success' => true,
                'message' => '訂單已成功刪除'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '刪除失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 記錄地標使用次數
     */
    private function recordLandmarkUsage($address, $landmarkId)
    {
        if ($landmarkId) {
            $landmark = Landmark::find($landmarkId);
            if ($landmark) {
                $landmark->incrementUsage();
            }
        }
    }

    /**
     * 更新地標使用次數 API
     */
    public function updateLandmarkUsage(Request $request)
    {
        $landmarkId = $request->get('landmark_id');

        if ($landmarkId) {
            $landmark = Landmark::find($landmarkId);
            if ($landmark) {
                $landmark->incrementUsage();

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false], 400);
    }

    /**
     * 取得客戶歷史訂單
     */
    public function getCustomerHistoryOrders(Customer $customer)
    {
        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('ride_date', 'desc')
            ->orderBy('ride_time', 'desc')
            ->limit(10)
            ->select([
                'id', 'ride_date', 'ride_time',
                'pickup_address', 'dropoff_address',
                'companions', 'wheelchair', 'stair_machine',
                'status', 'customer_phone'
            ])
            ->get();

        return response()->json($orders);
    }
    
    /**
     * 準備搜尋參數，確保新訂單能夠顯示
     */
    private function prepareSearchParams(Request $request, Order $newOrder)
    {
        $params = [];
        
        // 保留原有搜尋參數
        if ($request->filled('keyword')) {
            $params['keyword'] = $request->input('keyword');
        }
        
        if ($request->filled('customer_id')) {
            $params['customer_id'] = $request->input('customer_id');
        }
        
        // 智能處理日期範圍
        $newOrderDate = $newOrder->ride_date;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if ($startDate && $endDate) {
            // 如果原本有日期範圍，檢查新訂單是否在範圍內
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $newDate = Carbon::parse($newOrderDate);
            
            if ($newDate->lt($start)) {
                // 新訂單日期早於範圍開始，擴展開始日期
                $params['start_date'] = $newDate->format('Y-m-d');
                $params['end_date'] = $endDate;
            } elseif ($newDate->gt($end)) {
                // 新訂單日期晚於範圍結束，擴展結束日期
                $params['start_date'] = $startDate;
                $params['end_date'] = $newDate->format('Y-m-d');
            } else {
                // 新訂單在範圍內，保持原範圍
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }
        } elseif ($startDate) {
            // 只有開始日期
            $start = Carbon::parse($startDate);
            $newDate = Carbon::parse($newOrderDate);
            
            $params['start_date'] = $newDate->lt($start) ? $newDate->format('Y-m-d') : $startDate;
        } elseif ($endDate) {
            // 只有結束日期
            $end = Carbon::parse($endDate);
            $newDate = Carbon::parse($newOrderDate);
            
            $params['end_date'] = $newDate->gt($end) ? $newDate->format('Y-m-d') : $endDate;
        } else {
            // 沒有設定日期範圍，檢查新訂單是否是今天
            $today = Carbon::today();
            $newDate = Carbon::parse($newOrderDate);
            
            if (!$newDate->isSameDay($today)) {
                // 新訂單不是今天，設定適當的日期範圍
                if ($newDate->lt($today)) {
                    $params['start_date'] = $newDate->format('Y-m-d');
                    $params['end_date'] = $today->format('Y-m-d');
                } else {
                    $params['start_date'] = $today->format('Y-m-d');
                    $params['end_date'] = $newDate->format('Y-m-d');
                }
            }
        }
        
        return $params;
    }

    /**
     * 為批量建立的訂單準備搜尋參數
     */
    private function prepareBatchSearchParams(Request $request, array $orders)
    {
        $params = [];
        
        // 保留原有搜尋參數
        if ($request->filled('keyword')) {
            $params['keyword'] = $request->input('keyword');
        }
        
        if ($request->filled('customer_id')) {
            $params['customer_id'] = $request->input('customer_id');
        }
        
        if (empty($orders)) {
            return $params;
        }
        
        // 找出所有新建立訂單的日期範圍
        $orderDates = array_map(function($order) {
            return Carbon::parse($order->ride_date);
        }, $orders);
        
        $minNewDate = min($orderDates);
        $maxNewDate = max($orderDates);
        
        // 取得現有的搜尋範圍
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if ($startDate && $endDate) {
            // 如果原本有日期範圍，擴展範圍以包含所有新訂單
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            $params['start_date'] = $minNewDate->lt($start) ? $minNewDate->format('Y-m-d') : $start->format('Y-m-d');
            $params['end_date'] = $maxNewDate->gt($end) ? $maxNewDate->format('Y-m-d') : $end->format('Y-m-d');
        } elseif ($startDate) {
            // 只有開始日期
            $start = Carbon::parse($startDate);
            $params['start_date'] = $minNewDate->lt($start) ? $minNewDate->format('Y-m-d') : $start->format('Y-m-d');
            $params['end_date'] = $maxNewDate->format('Y-m-d');
        } elseif ($endDate) {
            // 只有結束日期
            $end = Carbon::parse($endDate);
            $params['start_date'] = $minNewDate->format('Y-m-d');
            $params['end_date'] = $maxNewDate->gt($end) ? $maxNewDate->format('Y-m-d') : $end->format('Y-m-d');
        } else {
            // 沒有設定日期範圍，設定範圍包含今天和所有新訂單
            $today = Carbon::today();
            $allDates = array_merge([$today], $orderDates);
            
            $absoluteMin = min($allDates);
            $absoluteMax = max($allDates);
            
            // 如果所有新訂單都是今天，則不設定範圍
            if (!$absoluteMin->isSameDay($absoluteMax)) {
                $params['start_date'] = $absoluteMin->format('Y-m-d');
                $params['end_date'] = $absoluteMax->format('Y-m-d');
            }
        }
        
        return $params;
    }

    /**
     * 檢查重複訂單的 API 端點
     */
    public function checkDuplicateOrder(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'ride_date' => 'required|date',
            'ride_time' => 'required|date_format:H:i',
            'order_id' => 'nullable|integer'
        ]);

        $query = Order::where('customer_id', $request->customer_id)
            ->where('ride_date', $request->ride_date)
            ->where('ride_time', $request->ride_time);

        // 編輯模式時排除當前訂單
        if ($request->order_id) {
            $query->where('id', '!=', $request->order_id);
        }

        $existingOrder = $query->first();

        return response()->json([
            'isDuplicate' => $existingOrder !== null,
            'message' => $existingOrder 
                ? '該客戶在此日期時間已有訂單（訂單編號：' . $existingOrder->order_number . '）'
                : '此時間可以使用',
            'existingOrder' => $existingOrder ? [
                'id' => $existingOrder->id,
                'order_number' => $existingOrder->order_number,
                'pickup_address' => $existingOrder->pickup_address,
                'dropoff_address' => $existingOrder->dropoff_address,
                'created_at' => $existingOrder->created_at->format('Y-m-d H:i')
            ] : null
        ]);
    }

    /**
     * 建立單人訂單
     */
    private function createSingleOrder($validated)
    {
        $typeCodeMap = [
            '新北長照' => 'NTPC',
            '台北長照' => 'TPC',
            '新北復康' => 'NTFK',
            '愛接送' => 'LT',
        ];

        $today = Carbon::now();
        $typeCode = $typeCodeMap[$validated['order_type']] ?? 'UNK';
        $idSuffix = substr($validated['customer_id_number'], -3);
        $date = $today->format('Ymd');
        $time = $today->format('Hi');
        
        $countToday = Order::whereDate('created_at', $today->toDateString())->count() + 1;
        $serial = str_pad($countToday, 4, '0', STR_PAD_LEFT);
        $orderNumber = $typeCode . $idSuffix . $date . $time . $serial;

        return Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $validated['customer_id'],
            'customer_name' => $validated['customer_name'],
            'customer_id_number' => $validated['customer_id_number'],
            'customer_phone' => $validated['customer_phone'],
            'order_type' => $validated['order_type'],
            'service_company' => $validated['service_company'],
            'ride_date' => $validated['ride_date'],
            'ride_time' => $validated['ride_time'],
            'pickup_address' => $validated['pickup_address'],
            'pickup_county' => $validated['pickup_county'],
            'pickup_district' => $validated['pickup_district'],
            'dropoff_address' => $validated['dropoff_address'],
            'dropoff_county' => $validated['dropoff_county'],
            'dropoff_district' => $validated['dropoff_district'],
            'wheelchair' => $validated['wheelchair'],
            'stair_machine' => $validated['stair_machine'],
            'companions' => $validated['companions'],
            'remark' => $validated['remark'] ?? null,
            'created_by' => $validated['created_by'],
            'identity' => $validated['identity'],
            'status' => $validated['status'],
            'special_status' => $validated['special_status'] ?? null,
        ]);
    }

    /**
     * 建立回程訂單
     */
    private function createReturnOrder($validated, $outboundOrder)
    {
        $today = Carbon::now();
        $typeCodeMap = [
            '新北長照' => 'NTPC',
            '台北長照' => 'TPC',
            '新北復康' => 'NTFK',
            '愛接送' => 'LT',
        ];
        
        $typeCode = $typeCodeMap[$validated['order_type']] ?? 'UNK';
        $idSuffix = substr($validated['customer_id_number'], -3);
        $date = $today->format('Ymd');
        $time = $today->format('Hi');
        
        $returnCountToday = Order::whereDate('created_at', $today->toDateString())->count() + 1;
        $returnSerial = str_pad($returnCountToday, 4, '0', STR_PAD_LEFT);
        $returnOrderNumber = $typeCode . $idSuffix . $date . $time . $returnSerial;

        return Order::create([
            'order_number' => $returnOrderNumber,
            'customer_id' => $validated['customer_id'],
            'customer_name' => $validated['customer_name'],
            'customer_id_number' => $validated['customer_id_number'],
            'customer_phone' => $validated['customer_phone'],
            'order_type' => $validated['order_type'],
            'service_company' => $validated['service_company'],
            'ride_date' => $validated['ride_date'],
            'ride_time' => $validated['back_time'], // 使用回程時間
            'pickup_address' => $validated['dropoff_address'], // 地址對調
            'pickup_county' => $validated['dropoff_county'],
            'pickup_district' => $validated['dropoff_district'],
            'dropoff_address' => $validated['pickup_address'], // 地址對調
            'dropoff_county' => $validated['pickup_county'],
            'dropoff_district' => $validated['pickup_district'],
            'wheelchair' => $validated['wheelchair'],
            'stair_machine' => $validated['stair_machine'],
            'companions' => $validated['companions'],
            'remark' => $validated['remark'] ?? null,
            'created_by' => $validated['created_by'],
            'identity' => $validated['identity'],
            'status' => $validated['status'],
            'special_status' => $validated['special_status'] ?? null,
        ]);
    }

    /**
     * 同步共乘群組駕駛變更
     */
    private function syncCarpoolGroupDriverChanges($groupId, $originalDriverId, $newDriverId)
    {
        // 如果駕駛ID沒有變更，無需同步
        if ($originalDriverId == $newDriverId) {
            return;
        }

        // 情況1: 從無到有 - 指派駕駛
        if (empty($originalDriverId) && !empty($newDriverId)) {
            $this->carpoolGroupService->assignDriverToGroup($groupId, $newDriverId);
            Log::info('共乘群組駕駛指派', [
                'group_id' => $groupId,
                'driver_id' => $newDriverId,
                'action' => 'assign'
            ]);
        }
        // 情況2: 從有到無 - 移除駕駛
        elseif (!empty($originalDriverId) && empty($newDriverId)) {
            $this->carpoolGroupService->unassignDriverFromGroup($groupId);
            Log::info('共乘群組駕駛移除', [
                'group_id' => $groupId,
                'original_driver_id' => $originalDriverId,
                'action' => 'unassign'
            ]);
        }
        // 情況3: 從有到有（不同駕駛）- 更換駕駛
        elseif (!empty($originalDriverId) && !empty($newDriverId) && $originalDriverId != $newDriverId) {
            $this->carpoolGroupService->assignDriverToGroup($groupId, $newDriverId);
            Log::info('共乘群組駕駛更換', [
                'group_id' => $groupId,
                'original_driver_id' => $originalDriverId,
                'new_driver_id' => $newDriverId,
                'action' => 'replace'
            ]);
        }
    }

    /**
     * 解析地址中的縣市區域資訊
     */
    private function extractAddressInfo($validated)
    {
        // 拆解上車地址資訊
        $pickupAddress = $validated['pickup_address'];
        preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $pickupAddress, $pickupMatches);
        $validated['pickup_county'] = $pickupMatches[1] ?? null;
        $validated['pickup_district'] = $pickupMatches[2] ?? null;
        
        // 拆解下車地址資訊
        $dropoffAddress = $validated['dropoff_address'];
        preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $dropoffAddress, $dropoffMatches);
        $validated['dropoff_county'] = $dropoffMatches[1] ?? null;
        $validated['dropoff_district'] = $dropoffMatches[2] ?? null;
        
        return $validated;
    }
}
