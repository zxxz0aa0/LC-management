<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Landmark;
use App\Models\Order;
use App\Rules\UniqueOrderDateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
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

        $typeCodeMap = [
            '新北長照' => 'NTPC',
            '台北長照' => 'TPC',
            '新北復康' => 'NTFK',
            '愛接送' => 'LT',
        ];

        // 取得當前時間
        $today = Carbon::now();

        // 取得類型代碼
        $orderType = $validated['order_type'] ?? null;
        $typeCode = $typeCodeMap[$orderType] ?? 'UNK'; // fallback 預設 UNK

        // 身分證末 3 碼
        $idNumber = $validated['customer_id_number'];
        $idSuffix = substr($idNumber, -3);

        // 日期時間
        $date = $today->format('Ymd');
        $time = $today->format('Hi'); // 例如 1230

        // 查詢當日已有幾張單，+1 後補滿 4 碼流水號
        $countToday = Order::whereDate('created_at', $today->toDateString())->count() + 1;
        $serial = str_pad($countToday, 4, '0', STR_PAD_LEFT);

        // 組合編號
        $orderNumber = $typeCode.$idSuffix.$date.$time.$serial;

        $order = Order::create([
            'order_number' => $orderNumber, // 1.訂單編號
            'customer_id' => $validated['customer_id'], // 2.客戶 ID
            'driver_id' => $validated['driver_id'] ?? null, // 3.駕駛 ID（可選填）
            'customer_name' => $validated['customer_name'], // 4.個案姓名
            'customer_id_number' => $validated['customer_id_number'], // 5. 個案身分證字號
            'customer_phone' => $validated['customer_phone'], // 6. 個案電話
            'driver_name' => $validated['driver_name'] ?? null, // 7. 駕駛姓名（可選填）
            'driver_fleet_number' => $validated['driver_fleet_number'] ?? null, // 7.1 駕駛姓名（可選填）
            'driver_plate_number' => $validated['driver_plate_number'] ?? null, // 8. 車牌號碼（可選填）
            'order_type' => $validated['order_type'], // 9.訂單類型
            'service_company' => $validated['service_company'], // 10. 服務單位
            'ride_date' => $validated['ride_date'], // 11. 用車日期
            'ride_time' => $validated['ride_time'], // 12. 用車時間
            'pickup_address' => $pickupAddress, // 13. 上車地址
            'pickup_county' => $validated['pickup_county'], // 14. 上車縣市
            'pickup_district' => $validated['pickup_district'], // 15. 上車區域
            'dropoff_address' => $dropoffAddress, // 16. 下車地址
            'dropoff_county' => $validated['dropoff_county'], // 17. 下車縣市
            'dropoff_district' => $validated['dropoff_district'], // 18. 下車區域
            'wheelchair' => $validated['wheelchair'], // 19. 是否需要輪椅
            'stair_machine' => $validated['stair_machine'], // 20. 是否需要爬梯機
            'companions' => $validated['companions'], // 21. 陪同人數
            'remark' => $validated['remark'] ?? null, // 22. 備註
            'created_by' => $validated['created_by'], // 23. 建單人員
            'identity' => $validated['identity'], // 24. 身份別
            'carpool_name' => $validated['carpoolSearchInput'] ?? null, // 25. 共乘對象
            'status' => $validated['status'], // 27. 訂單狀態
            'special_status' => $validated['special_status'] ?? null, // 28. 特別狀態（可選填）
            'carpool_customer_id' => $validated['carpool_customer_id'] ?? null, // 29. 共乘客戶ID（可選填）
            'carpool_id' => $validated['carpool_id_number'] ?? null, // 30. 共乘客戶身分證（可選填）

            // ... 其他欄位請自行加入
        ]);

        $returnOrder = null;
        $ordersCreated = 1;

        // 檢查是否有回程時間，如果有則創建回程訂單
        if (!empty($validated['back_time'])) {
            // 生成回程訂單編號（增加流水號避免重複）
            $returnCountToday = Order::whereDate('created_at', $today->toDateString())->count() + 1;
            $returnSerial = str_pad($returnCountToday, 4, '0', STR_PAD_LEFT);
            $returnOrderNumber = $typeCode.$idSuffix.$date.$time.$returnSerial;

            // 創建回程訂單（地址對調）
            $returnOrder = Order::create([
                'order_number' => $returnOrderNumber,
                'customer_id' => $validated['customer_id'],
                'driver_id' => $validated['driver_id'] ?? null,
                'customer_name' => $validated['customer_name'],
                'customer_id_number' => $validated['customer_id_number'],
                'customer_phone' => $validated['customer_phone'],
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_fleet_number' => $validated['driver_fleet_number'] ?? null,
                'driver_plate_number' => $validated['driver_plate_number'] ?? null,
                'order_type' => $validated['order_type'],
                'service_company' => $validated['service_company'],
                'ride_date' => $validated['ride_date'],
                'ride_time' => $validated['back_time'], // 使用回程時間
                'pickup_address' => $dropoffAddress, // 對調：原下車地址變上車地址
                'pickup_county' => $validated['dropoff_county'], // 對調：原下車縣市變上車縣市
                'pickup_district' => $validated['dropoff_district'], // 對調：原下車區域變上車區域
                'dropoff_address' => $pickupAddress, // 對調：原上車地址變下車地址
                'dropoff_county' => $validated['pickup_county'], // 對調：原上車縣市變下車縣市
                'dropoff_district' => $validated['pickup_district'], // 對調：原上車區域變下車區域
                'wheelchair' => $validated['wheelchair'],
                'stair_machine' => $validated['stair_machine'],
                'companions' => $validated['companions'],
                'remark' => $validated['remark'] ?? null,
                'created_by' => $validated['created_by'],
                'identity' => $validated['identity'],
                'carpool_name' => $validated['carpoolSearchInput'] ?? null,
                'status' => $validated['status'],
                'special_status' => $validated['special_status'] ?? null,
                'carpool_customer_id' => $validated['carpool_customer_id'] ?? null,
                'carpool_id' => $validated['carpool_id_number'] ?? null,
            ]);

            $ordersCreated = 2;

        }

        // 記錄去程訂單的地標使用次數
        $this->recordLandmarkUsage($request->get('pickup_address'), $request->get('pickup_landmark_id'));
        $this->recordLandmarkUsage($request->get('dropoff_address'), $request->get('dropoff_landmark_id'));

        if ($request->ajax()) {
            $query = Order::filter($request);
            $orders = $query->orderBy('ride_date', 'desc')->get();

            return view('orders.components.order-table', compact('orders'))->render(); // 回傳部分視圖
        }

        // 頁面式提交，成功後返回訂單列表並保持完整搜尋條件
        $redirectParams = $this->prepareSearchParams($request, $order);

        $successMessage = $ordersCreated === 2 
            ? '成功建立 2 筆訂單（去程和回程）' 
            : '訂單建立成功';

        return redirect()->route('orders.index', $redirectParams)->with('success', $successMessage);
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
            $validated['carpool_name'] = $validated['carpoolSearchInput'] ?? null;
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

            unset($validated['carpoolSearchInput'], $validated['carpool_id_number']);

            $order->update($validated);

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
}
