<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Customer;
use Carbon\Carbon;


class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::filter($request);

        // 如果沒有指定日期篩選，預設顯示今天的訂單
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereDate('ride_date', Carbon::today());
        }

        // 排序 & 分頁
        $orders = $query->latest()->paginate(50);

        // 如果你有客戶搜尋邏輯，要一起撈
        $customers = collect();
        if ($request->filled('keyword')) {
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

        if ($request->ajax()) {
            return view('orders.partials.form', compact('customer', 'user'));
        }

        return view('orders.create', compact('customer','user'),);


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
                'ride_time' => 'required|date_format:H:i',
                'pickup_address' => 'required|string|max:255',
                'dropoff_address' => 'required|string|max:255',
                'status' => 'required|in:open,assigned,replacement,blocked,cancelled',
                'companions' => 'required|integer|min:0',
                'order_type' => 'required|string',
                'service_company' => 'required|string',
                'wheelchair' => 'required|boolean',
                'stair_machine' => 'required|boolean',
                'remark' => 'nullable|string',
                'created_by' => 'required|string',
                'identity' => 'required|string',
                'carpool_name' => 'nullable|string',
                'special_order' => 'required|boolean',
                'special_status' => 'nullable|string',
                'carpool_customer_id' => 'nullable|integer',
                'carpool_id' => 'nullable|string',
                'driver_id' => 'nullable|integer',
                'driver_name' => 'nullable|string',
                'driver_plate_number' => 'nullable|string',
                'driver_fleet_number' => 'nullable|string',
                'carpoolSearchInput' => 'nullable|string',
                'carpool_id_number' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'html' => view('orders.partials.form', [
                        'customer' => Customer::find($request->input('customer_id')),
                        'user' => auth()->user(),
                    ])->withErrors(new \Illuminate\Support\MessageBag($e->errors()))->render()
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
        '愛接送'   => 'LT',
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
        $orderNumber = $typeCode . $idSuffix . $date . $time . $serial;


        Order::create([
            'order_number'       => $orderNumber,// 1.訂單編號
            'customer_id'        => $validated['customer_id'],// 2.客戶 ID
            'driver_id'          => $validated['driver_id'] ?? null,// 3.駕駛 ID（可選填）
            'customer_name'      => $validated['customer_name'],// 4.個案姓名
            'customer_id_number' => $validated['customer_id_number'],//5. 個案身分證字號
            'customer_phone'     => $validated['customer_phone'],//6. 個案電話
            'driver_name'        => $validated['driver_name'] ?? null,//7. 駕駛姓名（可選填）
            'driver_fleet_number' => $validated['driver_fleet_number'] ?? null,//7.1 駕駛姓名（可選填）
            'driver_plate_number'=> $validated['driver_plate_number'] ?? null,//8. 車牌號碼（可選填）
            'order_type'         => $validated['order_type'],// 9.訂單類型
            'service_company'    => $validated['service_company'],//10. 服務單位
            'ride_date'          => $validated['ride_date'],//11. 用車日期
            'ride_time'          => $validated['ride_time'],//12. 用車時間
            'pickup_address'     => $pickupAddress,//13. 上車地址
            'pickup_county'      => $validated['pickup_county'],//14. 上車縣市
            'pickup_district'    => $validated['pickup_district'],//15. 上車區域
            'dropoff_address'    => $dropoffAddress,//16. 下車地址
            'dropoff_county'     => $validated['dropoff_county'],//17. 下車縣市
            'dropoff_district'   => $validated['dropoff_district'],//18. 下車區域
            'wheelchair'         => $validated['wheelchair'],//19. 是否需要輪椅
            'stair_machine'      => $validated['stair_machine'],//20. 是否需要爬梯機
            'companions'         => $validated['companions'],//21. 陪同人數
            'remark'             => $validated['remark'] ?? null,//22. 備註
            'created_by'         => $validated['created_by'],//23. 建單人員
            'identity'           => $validated['identity'],//24. 身份別
            'carpool_name'       => $validated['carpoolSearchInput'] ?? null,//25. 共乘對象
            'special_order'      => $validated['special_order'],//26. 特別訂單
            'status'             => $validated['status'],//27. 訂單狀態
            'special_status'     => $validated['special_status'] ?? null,//28. 特別狀態（可選填）
            'carpool_customer_id' => $validated['carpool_customer_id'] ?? null, //29. 共乘客戶ID（可選填）
            'carpool_id'          => $validated['carpool_id_number'] ?? null, //30. 共乘客戶身分證（可選填）

            // ... 其他欄位請自行加入
        ]);

        if ($request->ajax()) {
            $query = Order::filter($request);
            $orders = $query->orderBy('ride_date', 'desc')->get();
            return view('orders.partials.list', compact('orders'))->render(); // 回傳部分視圖
        }

        return redirect()->route('orders.index')->with('success', '訂單建立成功');
    }


    // 顯示單筆訂單資料（預留）
    public function show(Order $order)
    {
        $driver = null;
        if ($order->driver_id) {
            $driver = \App\Models\Driver::find($order->driver_id);
        }
        return view('orders.partials.show', compact('order', 'driver'));
    }

    // 顯示編輯表單（預留）
    public function edit(Order $order)
    {
        // 如果是AJAX
        if (request()->ajax()) {
            return view('orders.partials.form', [
                'order' => $order,
                'customer' => $order->customer,
                'user' => auth()->user()
            ]);
        }

        // 如果直接進頁面
        return view('orders.edit', compact('order'));
    }
    // 更新訂單資料（預留）
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $validated = $request->validated();

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

        $order->update($validated);

        if ($request->ajax()) {
            $query = Order::filter($request);

            $orders = $query->orderBy('ride_date', 'desc')->get();
            return view('orders.partials.list', compact('orders'))->render();
        }

        return redirect()->route('orders.index')->with('success', '訂單更新成功');

    }

    // 刪除訂單（預留）
    public function destroy(Order $order)
    {
        // 等等再補功能
    }



}
