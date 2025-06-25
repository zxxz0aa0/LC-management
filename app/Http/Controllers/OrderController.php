<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Customer;
use Carbon\Carbon;


class OrderController extends Controller
{
    // 顯示所有訂單列表（預留）
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');

        $customers = collect();
        $orders = collect();

        // 🔍 如果有輸入搜尋關鍵字
        if ($keyword) {
            // 搜尋符合的客戶
            $customers = Customer::where('name', 'like', "%{$keyword}%")
                ->orWhereJsonContains('phone_number', $keyword)
                ->orWhere('id_number', 'like', "%{$keyword}%")
                ->get();

            // 取得這些客戶的 ID 清單
            $customerIds = $customers->pluck('id');

            // 撈出這些客戶的訂單
            $orders = Order::whereIn('customer_id', $customerIds)
                ->orderBy('ride_date', 'desc')
                ->get();
        } else {
            // 沒有搜尋，就顯示所有訂單
            $orders = Order::orderBy('ride_date', 'desc')->get();
        }

        

        return view('orders.index', compact('customers', 'orders'));
    }


    // 顯示新增訂單表單畫面
    public function create(Request $request)
    {
        $customer = null;

        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->input('customer_id'));
        }
        
        $user = auth()->user(); // 🔹目前登入的使用者

        
        

        return view('orders.create', compact('customer','user'),);

        
    }




    // 儲存新訂單資料（之後會補功能）

    public function store(Request $request)
    {
        
        $pickupAddress = $request->input('pickup_address');
        $dropoffAddress = $request->input('dropoff_address');

        // ✅ 驗證 pickup 地址格式
        if (!preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $pickupAddress, $pickupMatches)) {
            return back()->withErrors(['pickup_address' => '上車地址必須包含「市/縣」與「區/鄉/鎮」'])->withInput();
        }

        // ✅ 驗證 dropoff 地址格式
        if (!preg_match('/(.+市|.+縣)(.+區|.+鄉|.+鎮)/u', $dropoffAddress, $dropoffMatches)) {
            return back()->withErrors(['dropoff_address' => '下車地址必須包含「市/縣」與「區/鄉/鎮」'])->withInput();
        }

        // 拆出 pickup 地點
        $pickupCounty = $pickupMatches[1];
        $pickupDistrict = $pickupMatches[2];

        // 拆出 dropoff 地點
        $dropoffCounty = $dropoffMatches[1];
        $dropoffDistrict = $dropoffMatches[2];

        $typeCodeMap = [
        '新北長照' => 'NTPC',
        '台北長照' => 'TPC',
        '新北復康' => 'NTFK',
        '愛接送'   => 'LT',
        ];

        // 取得當前時間
        $today = Carbon::now();

        // 取得類型代碼
        $orderType = $request->input('order_type');
        $typeCode = $typeCodeMap[$orderType] ?? 'UNK'; // fallback 預設 UNK

        // 身分證末 3 碼
        $idNumber = $request->input('customer_id_number');
        $idSuffix = substr($idNumber, -3);

        // 日期時間
        $date = $today->format('Ymd');
        $time = $today->format('Hi'); // 例如 1230

        // 查詢當日已有幾張單，+1 後補滿 4 碼流水號
        $countToday = Order::whereDate('created_at', $today->toDateString())->count() + 1;
        $serial = str_pad($countToday, 4, '0', STR_PAD_LEFT);

        // 組合編號
        $orderNumber = $typeCode . $idSuffix . $date . $time . $serial;


        // ✅ 建立訂單（你可依實際欄位補齊其他欄位）
        Order::create([
            'order_number'       => $orderNumber,// 1.訂單編號
            'customer_id'        => $request->input('customer_id'),// 2.客戶 ID
            'driver_id'          => $request->input('driver_id', null),// 3.駕駛 ID（可選填）
            'customer_name'      => $request->input('customer_name'),// 4.個案姓名
            'customer_id_number' => $request->input('customer_id_number'),//5. 個案身分證字號
            'customer_phone'     => $request->input('customer_phone'),//6. 個案電話
            'driver_name'        => $request->input('driver_name',null),//7. 駕駛姓名（可選填）
            'driver_plate_number'=> $request->input('driver_plate_number',null),//8. 車牌號碼（可選填）
            'order_type'         => $request->input('order_type'),// 9.訂單類型
            'service_company'    => $request->input('service_company'),//10. 服務單位
            'ride_date'          => $request->input('ride_date'),//11. 用車日期
            'ride_time'          => $request->input('ride_time'),//12. 用車時間
            'pickup_address'     => $pickupAddress,//13. 上車地址
            'pickup_county'      => $pickupCounty,//14. 上車縣市
            'pickup_district'    => $pickupDistrict,//15. 上車區域
            'dropoff_address'    => $dropoffAddress,//16. 下車地址
            'dropoff_county'     => $dropoffCounty,//17. 下車縣市
            'dropoff_district'   => $dropoffDistrict,//18. 下車區域
            'wheelchair'         => $request->input('wheelchair'),//19. 是否需要輪椅
            'stair_machine'      => $request->input('stair_machine'),//20. 是否需要爬梯機
            'companions'         => $request->input('companions'),//21. 陪同人數
            'remark'             => $request->input('remark'),//22. 備註
            'created_by'         => $request->input('created_by'),//23. 建單人員
            'identity'           => $request->input('identity'),//24. 身份別
            'carpool_with'       => $request->input('carpool_with'),//25. 共乘對象
            'special_order'      => $request->input('special_order'),//26. 特別訂單
            'status'             => $request->input('status'),//27. 訂單狀態
            
            //資料表要新增：特別項目、共乘ID、少共乘對象、共乘身分證字號、共乘電話、共乘地址、共乘狀態、特別訂單種類
            // ... 其他欄位請自行加入
        ]);

        if ($request->ajax()) {
            $orders = Order::orderBy('ride_date', 'desc')->get();
        return view('orders.partials.list', compact('orders'))->render(); // 回傳部分視圖
        }

        return redirect()->route('orders.index')->with('success', '訂單建立成功');
    }


    // 顯示單筆訂單資料（預留）
    public function show(Order $order)
    {
        return view('orders.show', compact('order'));
    }

    // 顯示編輯表單（預留）
    public function edit(Order $order)
    {
        return view('orders.edit', compact('order'));
    }

    // 更新訂單資料（預留）
    public function update(Request $request, Order $order)
    {
        // 等等再補功能
    }

    // 刪除訂單（預留）
    public function destroy(Order $order)
    {
        // 等等再補功能
    }



}
