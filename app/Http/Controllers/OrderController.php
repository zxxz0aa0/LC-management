<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Exports\OrderTemplateExport;
use App\Exports\SimpleOrdersExport;
use App\Exports\SimpleOrderTemplateExport;
use App\Http\Requests\UpdateOrderRequest;
use App\Imports\OrdersImport;
use App\Imports\RowCountImport;
use App\Jobs\ProcessOrderImportJob;
use App\Models\Customer;
use App\Models\ImportProgress;
use App\Models\Landmark;
use App\Models\Order;
use App\Rules\UniqueOrderDateTime;
use App\Services\BatchOrderService;
use App\Services\CarpoolGroupService;
use App\Services\OrderNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

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

        // 預先載入客戶關聯，避免 N+1 查詢問題
        $query->with('customer');

        // 如果沒有指定日期篩選，預設顯示今天的訂單
        if (! $request->filled('start_date') && ! $request->filled('end_date')) {
            $query->whereDate('ride_date', Carbon::today());
        }

        // 排序（DataTable 將處理分頁）
        $orders = $query->latest()->get();

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
        $defaultStatus = 'open'; // 預設訂單狀態

        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->input('customer_id'));

            // 如果客戶的特殊狀態為「黑名單」，自動將訂單狀態預設為 blacklist
            if ($customer && $customer->special_status === '黑名單') {
                $defaultStatus = 'blacklist';
            }
        }

        $user = auth()->user(); // 🔹目前登入的使用者

        // 保留搜尋參數，讓返回按鈕能維持搜尋狀態
        $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine', 'service_company']);

        if ($request->ajax()) {
            return view('orders.create', compact('customer', 'user', 'searchParams', 'defaultStatus'));
        }

        return view('orders.create', compact('customer', 'user', 'searchParams', 'defaultStatus'));
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
                    new UniqueOrderDateTime($request->customer_id, $request->ride_date, $request->back_time),
                ],
                'back_time' => 'nullable|date_format:H:i',
                'pickup_address' => [
                    'required',
                    'string',
                    'regex:/^(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮).+$/u',
                ],
                'dropoff_address' => [
                    'required',
                    'string',
                    'regex:/^(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮).+$/u',
                ],
                'status' => 'required|in:open,assigned,bkorder,blocked,cancelled,cancelledOOC,cancelledNOC,cancelledCOTD,blacklist,no_send,regular_sedans,no_car',
                'companions' => 'required|integer|min:0',
                'order_type' => 'required|string',
                'service_company' => 'required|string',
                'wheelchair' => 'required|in:是,否,未知',
                'stair_machine' => 'required|in:是,否,未知',

                // 共乘相關欄位
                'carpool_customer_id' => 'nullable|exists:customers,id',
                'remark' => 'nullable|string',
                'created_by' => 'required|string',
                'identity' => 'nullable|string',
                'carpool_name' => 'nullable|string',
                'special_status' => 'nullable|string',
                'carpool_customer_id' => 'nullable|integer',
                'carpool_id' => 'nullable|string',
                'driver_id' => 'nullable|integer',
                'driver_name' => 'nullable|string',
                'driver_plate_number' => 'nullable|string',
                'driver_fleet_number' => 'nullable|string',

                // 回程駕駛相關欄位
                'return_driver_id' => 'nullable|integer',
                'return_driver_name' => 'nullable|string',
                'return_driver_plate_number' => 'nullable|string',
                'return_driver_fleet_number' => 'nullable|string',

                'carpoolSearchInput' => 'nullable|string',
                'carpool_id_number' => 'nullable|string',
            ], [
                'customer_phone.required' => '客戶電話為必填欄位',
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

        preg_match('/(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮)/u', $pickupAddress, $pickupMatches);
        $validated['pickup_county'] = $pickupMatches[1] ?? null;
        $validated['pickup_district'] = $pickupMatches[2] ?? null;

        preg_match('/(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮)/u', $dropoffAddress, $dropoffMatches);
        $validated['dropoff_county'] = $dropoffMatches[1] ?? null;
        $validated['dropoff_district'] = $dropoffMatches[2] ?? null;

        // 【新增】地址驗證（台北長照、新北長照）
        $addressValidationService = app(\App\Services\AddressValidationService::class);
        $addressValidation = $addressValidationService->validateOrderAddresses(
            $validated['order_type'],
            $pickupAddress,
            $dropoffAddress
        );

        // 驗證失敗則返回錯誤
        if (! $addressValidation['valid']) {
            $errors = [];
            if (! empty($addressValidation['errors']['pickup'])) {
                $errors['pickup_address'] = $addressValidation['errors']['pickup'];
            }
            if (! empty($addressValidation['errors']['dropoff'])) {
                $errors['dropoff_address'] = $addressValidation['errors']['dropoff'];
            }

            return back()->withErrors($errors)->withInput();
        }

        // 自動設定「不派遣」狀態（新北長照特定區域）
        if ($addressValidation['auto_no_send']) {
            $validated['status'] = 'no_send';
        }

        // 檢查是否為共乘訂單
        $isCarpool = ! empty($validated['carpool_customer_id']);

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
            if (! empty($validated['back_time'])) {
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
                    'regex:/^(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮).+$/u',
                ],
                'dropoff_address' => [
                    'required',
                    'string',
                    'regex:/^(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮).+$/u',
                ],
                'companions' => 'required|integer|min:0',
                'wheelchair' => 'required|in:是,否,未知',
                'stair_machine' => 'required|in:是,否,未知',
                'remark' => 'nullable|string',
                'carpool_customer_id' => 'nullable|integer',
                'carpool_name' => 'nullable|string',
                'carpool_id' => 'nullable|string',
                'special_status' => 'nullable|string',
                'identity' => 'nullable|string',
                'created_by' => 'required|string',
                'status' => 'required|in:open,assigned,bkorder,blocked,cancelled,cancelledOOC,cancelledNOC,cancelledCOTD,blacklist,no_send,regular_sedans,no_car',
            ];

            // 根據日期模式添加特定驗證規則
            if ($request->input('date_mode') === 'manual') {
                $rules['selected_dates'] = [
                    'required',
                    'array',
                    'min:1',
                    'max:50',
                ];
                // 移除 after:today 限制，允許選擇過去日期（用於補建訂單）
                $rules['selected_dates.*'] = 'date';
            } elseif ($request->input('date_mode') === 'recurring') {
                // 移除 after:today 限制，允許選擇過去日期（用於補建訂單）
                $rules['start_date'] = 'required|date';
                $rules['end_date'] = 'required|date|after:start_date';
                $rules['weekdays'] = 'required|array|min:1|max:7';
                $rules['weekdays.*'] = 'integer|between:0,6';
                $rules['recurrence_type'] = 'required|in:weekly,biweekly,monthly';
            }

            // 添加調試資訊
            \Log::info('Batch order request data:', [
                'date_mode' => $request->input('date_mode'),
                'selected_dates' => $request->input('selected_dates'),
                'has_selected_dates' => $request->has('selected_dates'),
                'selected_dates_count' => is_array($request->input('selected_dates')) ? count($request->input('selected_dates')) : 0,
            ]);

            // 自訂驗證訊息
            $messages = [
                'customer_phone.required' => '客戶電話為必填欄位',
                'pickup_address.regex' => '上車地址必須包含「市/縣」與「區/鄉/鎮」',
                'dropoff_address.regex' => '下車地址必須包含「市/縣」與「區/鄉/鎮」',
            ];

            $validated = $request->validate($rules, $messages);

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
                $message .= '（失敗日期：'.implode(', ', $failedDates).'）';
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
                'request_data' => $request->all(),
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
        $searchParams = request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']);

        return view('orders.show', compact('order', 'driver', 'searchParams'));
    }

    // 顯示編輯表單（預留）
    public function edit(Order $order)
    {
        // 保留搜尋參數，讓返回按鈕能維持搜尋狀態
        $searchParams = request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']);

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
            preg_match('/(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮)/u', $pickupAddress, $pickupMatches);
            $validated['pickup_county'] = $pickupMatches[1] ?? null;
            $validated['pickup_district'] = $pickupMatches[2] ?? null;

            // 拆出 dropoff 地點
            preg_match('/(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮)/u', $dropoffAddress, $dropoffMatches);
            $validated['dropoff_county'] = $dropoffMatches[1] ?? null;
            $validated['dropoff_district'] = $dropoffMatches[2] ?? null;

            // 【新增】地址驗證（編輯時顯示警告但允許儲存）
            $addressValidationService = app(\App\Services\AddressValidationService::class);
            $addressValidation = $addressValidationService->validateOrderAddresses(
                $validated['order_type'],
                $pickupAddress,
                $dropoffAddress
            );

            // 驗證失敗則顯示警告訊息（但不阻止更新）
            if (! $addressValidation['valid']) {
                $warningMessages = [];
                if (! empty($addressValidation['errors']['pickup'])) {
                    $warningMessages = array_merge($warningMessages, $addressValidation['errors']['pickup']);
                }
                if (! empty($addressValidation['errors']['dropoff'])) {
                    $warningMessages = array_merge($warningMessages, $addressValidation['errors']['dropoff']);
                }
                session()->flash('address_warning', '⚠️ 警告：'.implode('；', $warningMessages));
            }

            // 如果是新北長照特定區域，提示將設為不派遣（但不強制）
            if ($addressValidation['auto_no_send'] && $validated['status'] !== 'no_send') {
                $districtName = $addressValidationService->getNoSendDistrictName($pickupAddress);
                session()->flash('no_send_suggestion', "ℹ️ 提示：上車地址位於 {$districtName}，建議將訂單狀態設為「不派遣」");
            }

            unset($validated['carpoolSearchInput'], $validated['carpool_id_number'], $validated['carpool_with']);

            // 記錄原始駕駛ID，用於檢測駕駛變更
            $originalDriverId = $order->driver_id;
            $newDriverId = $validated['driver_id'] ?? null;

            // 記錄更新人員
            $validated['updated_by'] = auth()->id();

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

            // 取得搜尋參數以保持列表頁面的搜尋狀態
            $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'service_company']);

            // 處理 customer_id 參數 (表單中用 search_customer_id 避免與資料庫欄位衝突)
            if ($request->filled('search_customer_id')) {
                $searchParams['customer_id'] = $request->get('search_customer_id');
            }

            return redirect()->route('orders.index', $searchParams)->with('success', '訂單更新成功');
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

    // 取消訂單
    public function cancel(Order $order, Request $request)
    {
        try {
            // 檢查訂單狀態是否可以取消
            $cancellableStatuses = ['open', 'assigned', 'bkorder']; // 🔹允許取消的狀態

            if (! in_array($order->status, $cancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => '此訂單狀態無法取消',
                ], 400);
            }

            // 取得取消原因，預設為一般取消
            $cancelReason = $request->input('cancel_reason', 'cancelled');

            // 驗證取消原因是否有效
            $validCancelReasons = [
                'cancelled',      // 一般取消
                'cancelledOOC',   // 別家有車
                'cancelledNOC',   // !取消
                'cancelledCOTD',   // X取消
            ];

            if (! in_array($cancelReason, $validCancelReasons)) {
                return response()->json([
                    'success' => false,
                    'message' => '無效的取消原因',
                ], 400);
            }

            // 取消原因對應的中文說明
            $cancelMessages = [
                'cancelled' => '訂單已取消',
                'cancelledOOC' => '訂單已取消（別家有車）',
                'cancelledNOC' => '訂單已取消（!取消）',
                'cancelledCOTD' => '訂單已取消（X取消）',
            ];

            // 取得取消原因說明（選填）
            $cancellationReasonText = $request->input('cancellation_reason_text');

            // 更新訂單狀態
            $order->update([
                'status' => $cancelReason,
                'cancellation_reason' => $cancellationReasonText, // 儲存取消原因說明
                'updated_by' => auth()->id(), // 記錄取消人員
            ]);

            return response()->json([
                'success' => true,
                'message' => $cancelMessages[$cancelReason],
                'new_status' => $cancelReason,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取消失敗：'.$e->getMessage(),
            ], 500);
        }
    }

    // 更新搓合時間
    public function updateMatchTime(Order $order, Request $request)
    {
        try {
            // 驗證輸入
            $validated = $request->validate([
                'match_time' => 'nullable|date_format:Y-m-d H:i:s',
            ]);

            // 更新 match_time
            $order->update([
                'match_time' => $validated['match_time'],
                'updated_by' => auth()->id(), // 記錄修改人員
            ]);

            $message = $validated['match_time'] ? '搓合時間更新成功' : '搓合時間已清除';

            return response()->json([
                'success' => true,
                'message' => $message,
                'new_match_time' => $order->match_time ? $order->match_time->format('Y-m-d H:i') : null,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '時間格式錯誤',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新失敗：'.$e->getMessage(),
            ], 500);
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
                'message' => '訂單已成功刪除',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '刪除失敗：'.$e->getMessage(),
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
                'status', 'customer_phone',
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

            if (! $newDate->isSameDay($today)) {
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
        $orderDates = array_map(function ($order) {
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
            if (! $absoluteMin->isSameDay($absoluteMax)) {
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
            'order_id' => 'nullable|integer',
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
                ? '該客戶在此日期時間已有訂單（訂單編號：'.$existingOrder->order_number.'）'
                : '此時間可以使用',
            'existingOrder' => $existingOrder ? [
                'id' => $existingOrder->id,
                'order_number' => $existingOrder->order_number,
                'pickup_address' => $existingOrder->pickup_address,
                'dropoff_address' => $existingOrder->dropoff_address,
                'created_at' => $existingOrder->created_at->format('Y-m-d H:i'),
            ] : null,
        ]);
    }

    /**
     * 檢查日期與上車點重複的 API 端點
     */
    public function checkDatePickupDuplicate(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'ride_date' => 'required|date',
            'pickup_address' => 'required|string',
            'order_id' => 'nullable|integer',
        ]);

        $query = Order::where('customer_id', $request->customer_id)
            ->where('ride_date', $request->ride_date)
            ->where('pickup_address', $request->pickup_address);

        // 編輯模式時排除當前訂單
        if ($request->order_id) {
            $query->where('id', '!=', $request->order_id);
        }

        $existingOrder = $query->first();

        return response()->json([
            'isDuplicate' => $existingOrder !== null,
            'message' => $existingOrder
                ? '該客戶在此日期地點已有訂單（訂單編號：'.$existingOrder->order_number.'）'
                : '此日期地點可以使用',
            'existingOrder' => $existingOrder ? [
                'id' => $existingOrder->id,
                'order_number' => $existingOrder->order_number,
                'ride_time' => $existingOrder->ride_time,
                'dropoff_address' => $existingOrder->dropoff_address,
                'created_at' => $existingOrder->created_at->format('Y-m-d H:i'),
            ] : null,
        ]);
    }

    /**
     * 批量檢查重複訂單
     */
    public function checkBatchDuplicateOrders(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'dates' => 'required|array|min:1|max:50',
            'dates.*' => 'date',
            'ride_time' => 'required|date_format:H:i',
            'order_id' => 'nullable|integer',
        ]);

        $customerId = $request->customer_id;
        $dates = $request->dates;
        $rideTime = $request->ride_time;
        $orderId = $request->order_id;

        // 查詢所有可能重複的訂單 - 使用 DATE() 函數確保純日期比對
        $query = Order::where('customer_id', $customerId)
            ->where('ride_time', $rideTime);

        // 使用 whereIn 和 DATE() 函數進行日期比對
        $query->where(function ($q) use ($dates) {
            foreach ($dates as $date) {
                $q->orWhereRaw('DATE(ride_date) = ?', [$date]);
            }
        });

        // 編輯模式時排除當前訂單
        if ($orderId) {
            $query->where('id', '!=', $orderId);
        }

        $existingOrders = $query->get();

        // 除錯資訊
        \Log::info('批量重複檢查除錯', [
            'customer_id' => $customerId,
            'ride_time' => $rideTime,
            'dates' => $dates,
            'existing_orders_count' => $existingOrders->count(),
            'existing_orders' => $existingOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'ride_date' => $order->ride_date,
                    'ride_time' => $order->ride_time,
                    'order_number' => $order->order_number,
                ];
            })->toArray(),
        ]);

        // 組織回應資料
        $duplicates = [];
        $availableDates = [];

        foreach ($dates as $date) {
            // 使用 Carbon 進行日期比對，確保格式一致
            $existing = $existingOrders->first(function ($order) use ($date) {
                return Carbon::parse($order->ride_date)->format('Y-m-d') === $date;
            });

            if ($existing) {
                $duplicates[] = [
                    'date' => $date,
                    'formatted_date' => Carbon::parse($date)->format('Y-m-d (D)'),
                    'existing_order' => [
                        'id' => $existing->id,
                        'order_number' => $existing->order_number,
                        'pickup_address' => $existing->pickup_address,
                        'dropoff_address' => $existing->dropoff_address,
                        'status' => $existing->status,
                        'created_at' => $existing->created_at->format('Y-m-d H:i'),
                    ],
                ];
            } else {
                $availableDates[] = $date;
            }
        }

        $hasDuplicates = count($duplicates) > 0;
        $totalDates = count($dates);
        $duplicateCount = count($duplicates);
        $availableCount = count($availableDates);

        return response()->json([
            'hasDuplicates' => $hasDuplicates,
            'summary' => [
                'total' => $totalDates,
                'duplicates' => $duplicateCount,
                'available' => $availableCount,
            ],
            'message' => $hasDuplicates
                ? "發現 {$duplicateCount} 個重複日期，{$availableCount} 個日期可用"
                : "所有 {$totalDates} 個日期都可以使用",
            'duplicates' => $duplicates,
            'available_dates' => $availableDates,
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
        $orderNumber = $typeCode.$idSuffix.$date.$time.$serial;

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

            // 駕駛資訊
            'driver_id' => $validated['driver_id'] ?? null,
            'driver_name' => $validated['driver_name'] ?? null,
            'driver_plate_number' => $validated['driver_plate_number'] ?? null,
            'driver_fleet_number' => $validated['driver_fleet_number'] ?? null,
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
        $returnOrderNumber = $typeCode.$idSuffix.$date.$time.$returnSerial;

        // 處理回程駕駛資訊：如果有填入回程駕駛，使用回程駕駛；否則留空
        $returnDriverData = [];
        if (! empty($validated['return_driver_fleet_number']) || ! empty($validated['return_driver_name'])) {
            $returnDriverData = [
                'driver_id' => $validated['return_driver_id'] ?? null,
                'driver_name' => $validated['return_driver_name'] ?? null,
                'driver_plate_number' => $validated['return_driver_plate_number'] ?? null,
                'driver_fleet_number' => $validated['return_driver_fleet_number'] ?? null,
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
        ] + $returnDriverData);
    }

    /**
     * 快速指派駕駛給訂單（列表頁使用）
     */
    public function assignDriver(Request $request, Order $order)
    {
        try {
            // 驗證駕駛資訊
            $validated = $request->validate([
                'driver_id' => 'nullable|exists:drivers,id',
                'driver_name' => 'nullable|string|max:255',
                'driver_plate_number' => 'nullable|string|max:255',
                'driver_fleet_number' => 'nullable|string|max:255',
            ]);

            // 記錄原始駕駛資訊（用於日誌和共乘群組同步）
            $originalDriverId = $order->driver_id;
            $originalDriverName = $order->driver_name;
            $newDriverId = $validated['driver_id'];
            $newDriverName = $validated['driver_name'];

            // 更新訂單駕駛資訊
            $order->update([
                'driver_id' => $validated['driver_id'],
                'driver_name' => $validated['driver_name'],
                'driver_plate_number' => $validated['driver_plate_number'],
                'driver_fleet_number' => $validated['driver_fleet_number'],
                'status' => $validated['driver_id'] ? 'assigned' : 'open', // 有駕駛=已指派，無駕駛=可派遣
                'updated_by' => auth()->id(),
            ]);

            // 如果是共乘訂單，同步群組駕駛
            if ($order->carpool_group_id) {
                $this->syncCarpoolGroupDriverChanges(
                    $order->carpool_group_id,
                    $originalDriverId,
                    $newDriverId
                );
            }

            // 記錄操作日誌
            $action = $this->determineDriverAction($originalDriverId, $newDriverId);
            Log::info('訂單駕駛快速指派', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'action' => $action,
                'original_driver_id' => $originalDriverId,
                'original_driver_name' => $originalDriverName,
                'new_driver_id' => $newDriverId,
                'new_driver_name' => $newDriverName,
                'carpool_group_id' => $order->carpool_group_id,
                'operated_by' => auth()->user()->name ?? 'Unknown',
                'operated_at' => now()->toDateTimeString(),
            ]);

            // 產生成功訊息
            $message = $this->generateDriverActionMessage($action, $newDriverName);

            return response()->json([
                'success' => true,
                'message' => $message,
                'driver_id' => $order->driver_id,
                'driver_name' => $order->driver_name,
                'driver_plate_number' => $order->driver_plate_number,
                'driver_fleet_number' => $order->driver_fleet_number,
                'status' => $order->status,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗：'.$e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('駕駛快速指派失敗', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '指派失敗：'.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 判斷駕駛操作類型
     */
    private function determineDriverAction($originalDriverId, $newDriverId)
    {
        if (empty($originalDriverId) && ! empty($newDriverId)) {
            return 'assign'; // 指派駕駛
        } elseif (! empty($originalDriverId) && empty($newDriverId)) {
            return 'clear'; // 清除駕駛
        } elseif (! empty($originalDriverId) && ! empty($newDriverId) && $originalDriverId != $newDriverId) {
            return 'replace'; // 更換駕駛
        } else {
            return 'no_change'; // 無變更
        }
    }

    /**
     * 產生駕駛操作訊息
     */
    private function generateDriverActionMessage($action, $driverName)
    {
        switch ($action) {
            case 'assign':
                return "已成功指派駕駛：{$driverName}";
            case 'clear':
                return '已清除駕駛資訊，訂單狀態恢復為「可派遣」';
            case 'replace':
                return "已更換駕駛為：{$driverName}";
            default:
                return '操作完成';
        }
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
        if (empty($originalDriverId) && ! empty($newDriverId)) {
            $this->carpoolGroupService->assignDriverToGroup($groupId, $newDriverId);
            Log::info('共乘群組駕駛指派', [
                'group_id' => $groupId,
                'driver_id' => $newDriverId,
                'action' => 'assign',
            ]);
        }
        // 情況2: 從有到無 - 移除駕駛
        elseif (! empty($originalDriverId) && empty($newDriverId)) {
            $this->carpoolGroupService->unassignDriverFromGroup($groupId);
            Log::info('共乘群組駕駛移除', [
                'group_id' => $groupId,
                'original_driver_id' => $originalDriverId,
                'action' => 'unassign',
            ]);
        }
        // 情況3: 從有到有（不同駕駛）- 更換駕駛
        elseif (! empty($originalDriverId) && ! empty($newDriverId) && $originalDriverId != $newDriverId) {
            $this->carpoolGroupService->assignDriverToGroup($groupId, $newDriverId);
            Log::info('共乘群組駕駛更換', [
                'group_id' => $groupId,
                'original_driver_id' => $originalDriverId,
                'new_driver_id' => $newDriverId,
                'action' => 'replace',
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
        preg_match('/(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮)/u', $pickupAddress, $pickupMatches);
        $validated['pickup_county'] = $pickupMatches[1] ?? null;
        $validated['pickup_district'] = $pickupMatches[2] ?? null;

        // 拆解下車地址資訊
        $dropoffAddress = $validated['dropoff_address'];
        preg_match('/(.+?市|.+?縣)(.+?區|.+?鄉|.+?鎮)/u', $dropoffAddress, $dropoffMatches);
        $validated['dropoff_county'] = $dropoffMatches[1] ?? null;
        $validated['dropoff_district'] = $dropoffMatches[2] ?? null;

        return $validated;
    }

    // 匯出 Excel (完整格式)
    public function export(Request $request)
    {
        return Excel::download(new OrdersExport($request), 'orders.xlsx');
    }

    // 匯出 Excel (簡化格式)
    public function exportSimple(Request $request)
    {
        return Excel::download(new SimpleOrdersExport($request), 'orders_simple.xlsx');
    }

    // 匯出 Excel (簡化格式 - 依建立時間範圍和/或用車日期)
    public function exportSimpleByDate(Request $request)
    {
        // 驗證輸入參數
        $request->validate([
            'filter_mode' => 'required|in:created_at,ride_date,both',
            'created_start_date' => 'nullable|date',
            'created_end_date' => 'nullable|date|after_or_equal:created_start_date',
            'ride_date' => 'nullable|date',
        ]);

        $filterMode = $request->input('filter_mode');
        $filenameComponents = ['訂單匯出_簡化格式'];

        // 根據篩選模式驗證必要欄位
        if ($filterMode === 'created_at' || $filterMode === 'both') {
            if (! $request->has('created_start_date') || ! $request->has('created_end_date')) {
                return back()->withErrors(['created_date' => '請選擇建立時間範圍']);
            }
        }

        if ($filterMode === 'ride_date' || $filterMode === 'both') {
            if (! $request->has('ride_date')) {
                return back()->withErrors(['ride_date' => '請選擇用車日期']);
            }
        }

        // 處理建立時間範圍
        $createdStartDate = null;
        $createdEndDate = null;
        if ($request->has('created_start_date') && $request->has('created_end_date')) {
            $createdStartDate = Carbon::parse($request->created_start_date)->startOfMinute();
            $createdEndDate = Carbon::parse($request->created_end_date)->endOfMinute();

            // 檢查時間範圍合理性（避免過大範圍影響效能）
            if ($createdStartDate->diffInDays($createdEndDate) > 365) {
                return back()->withErrors(['date_range' => '建立時間範圍不得超過一年']);
            }

            $filenameComponents[] = sprintf(
                '建立%s至%s',
                $createdStartDate->format('Y-m-d'),
                $createdEndDate->format('Y-m-d')
            );
        }

        // 處理用車日期
        $rideDate = null;
        if ($request->has('ride_date')) {
            $rideDate = Carbon::parse($request->ride_date);
            $filenameComponents[] = sprintf('用車%s', $rideDate->format('Y-m-d'));
        }

        // 生成檔名
        $filename = implode('_', $filenameComponents).'.xlsx';

        // 創建一個臨時的 Request 物件來傳遞篩選條件
        $tempRequestData = [
            'filter_mode' => $filterMode,
        ];

        if ($createdStartDate && $createdEndDate) {
            $tempRequestData['created_at_start'] = $createdStartDate->toDateTimeString();
            $tempRequestData['created_at_end'] = $createdEndDate->toDateTimeString();
        }

        if ($rideDate) {
            $tempRequestData['ride_date'] = $rideDate->toDateString();
        }

        $tempRequest = new Request($tempRequestData);

        return Excel::download(new SimpleOrdersExport($tempRequest), $filename);
    }

    // 處理匯入
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        // 檢查檔案大小，決定處理方式
        $fileSize = $request->file('file')->getSize();
        $estimatedRows = $fileSize / 1024; // 粗估行數

        if ($estimatedRows > 1000) {
            return $this->queuedImport($request);
        }

        $importer = new OrdersImport;
        Excel::import($importer, $request->file('file'));

        $success = $importer->successCount;
        $fail = $importer->skipCount;
        $errors = $importer->errorMessages;

        return redirect()->route('orders.index')->with([
            'success' => "匯入完成：成功 {$success} 筆，失敗 {$fail} 筆。",
            'import_errors' => $errors,
        ]);
    }

    // 處理佇列匯入 (適用於大量資料)
    public function queuedImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $batchId = (string) Str::uuid();
        $filename = $request->file('file')->getClientOriginalName();

        // 儲存檔案
        $filePath = $request->file('file')->store('imports', 'local');

        // 預先讀取檔案計算總行數
        $rowCounter = new RowCountImport;
        Excel::import($rowCounter, storage_path('app/'.$filePath));
        $totalRows = $rowCounter->getRowCount();

        // 建立進度記錄
        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'orders',
            'filename' => $filename,
            'file_path' => $filePath,
            'total_rows' => $totalRows,
            'status' => 'pending',
        ]);

        // 派發佇列任務處理匯入
        ProcessOrderImportJob::dispatch($batchId, $filePath);

        return redirect()->route('orders.import.progress', ['batchId' => $batchId])
            ->with('success', "匯入已開始處理，總共 {$totalRows} 筆資料。請稍候並監控進度。");
    }

    // 查詢匯入進度
    public function importProgress($batchId)
    {
        $progress = ImportProgress::where('batch_id', $batchId)->firstOrFail();

        return view('orders.import-progress', compact('progress'));
    }

    // API: 取得匯入進度 JSON
    public function getImportProgress($batchId)
    {
        $progress = ImportProgress::where('batch_id', $batchId)->first();

        if (! $progress) {
            return response()->json(['error' => '找不到匯入記錄'], 404);
        }

        return response()->json($progress);
    }

    // 啟動佇列處理
    public function startQueueWorker(Request $request)
    {
        $batchId = $request->input('batch_id');

        // 檢查匯入記錄是否存在
        $importProgress = ImportProgress::where('batch_id', $batchId)->first();

        if (! $importProgress) {
            return response()->json([
                'success' => false,
                'message' => '找不到匯入記錄',
            ], 404);
        }

        // 檢查狀態是否為 pending
        if ($importProgress->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => '任務已經在處理中或已完成',
            ], 400);
        }

        try {
            // 檢查匯入類型並啟動相應的處理
            if ($importProgress->type === 'orders') {
                // 訂單匯入：使用資料庫中存儲的檔案路徑
                $filePath = $importProgress->file_path;

                if (! $filePath || ! \Storage::exists($filePath)) {
                    return response()->json([
                        'success' => false,
                        'message' => '匯入檔案不存在，請重新上傳',
                    ], 404);
                }

                // 直接同步執行匯入處理（適合 XAMPP/Windows 環境）
                try {
                    Log::info('開始同步執行訂單匯入', ['batch_id' => $batchId]);

                    // 更新狀態為處理中
                    $importProgress->update([
                        'status' => 'processing',
                        'started_at' => now(),
                    ]);

                    // 設定記憶體和執行時間限制
                    ini_set('memory_limit', '3G');
                    ini_set('max_execution_time', 7200);
                    set_time_limit(7200);

                    // 啟用垃圾回收
                    gc_enable();

                    // 建立匯入處理實例
                    $importer = new OrdersImport;

                    // 執行匯入
                    Excel::import($importer, storage_path('app/'.$filePath));

                    // 更新最終狀態
                    $importProgress->update([
                        'processed_rows' => $importer->successCount + $importer->skipCount,
                        'success_count' => $importer->successCount,
                        'error_count' => $importer->skipCount,
                        'error_messages' => $importer->errorMessages,
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    Log::info('訂單匯入同步執行完成', [
                        'batch_id' => $batchId,
                        'success_count' => $importer->successCount,
                        'error_count' => $importer->skipCount,
                    ]);

                    // 清理檔案
                    \Storage::delete($filePath);

                    return response()->json([
                        'success' => true,
                        'message' => '訂單匯入處理已完成',
                        'stats' => [
                            'success' => $importer->successCount,
                            'errors' => $importer->skipCount,
                        ],
                    ]);

                } catch (\Exception $e) {
                    Log::error('同步執行訂單匯入失敗', [
                        'batch_id' => $batchId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // 更新狀態為失敗
                    $errorMessages = $importProgress->error_messages ?? [];
                    $errorMessages[] = '匯入處理失敗: '.$e->getMessage();

                    $importProgress->update([
                        'status' => 'failed',
                        'error_messages' => $errorMessages,
                        'completed_at' => now(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => '匯入處理失敗: '.$e->getMessage(),
                    ], 500);
                }
            } else {
                // 客戶匯入：使用原有的queue:work方式處理
                $command = 'php artisan queue:work --once';
                $output = shell_exec($command.' 2>&1');

                return response()->json([
                    'success' => true,
                    'message' => '佇列處理已啟動',
                    'output' => $output,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('啟動處理失敗', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '啟動處理失敗：'.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 下載訂單匯入範例檔案 (完整格式)
     */
    public function downloadTemplate()
    {
        return Excel::download(new OrderTemplateExport, '訂單匯入範例檔案.xlsx');
    }

    /**
     * 下載訂單匯入範例檔案 (簡化格式)
     */
    public function downloadSimpleTemplate()
    {
        return Excel::download(new SimpleOrderTemplateExport, '訂單匯入範例檔案_簡化版.xlsx');
    }

    /**
     * 批量更新匯入 - 根據訂單編號更新訂單資訊
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $importer = new \App\Imports\OrderBatchUpdateImport;
            Excel::import($importer, $request->file('file'));

            $processedRows = $importer->processedRowCount; // Excel 行數
            $totalOrders = $importer->successCount; // 實際訂單數
            $carpoolSync = $importer->carpoolSyncCount; // 共乘同步數
            $fail = $importer->skipCount;
            $errors = $importer->errorMessages;

            // 建構成功訊息
            $successMessage = "批量更新完成：成功 {$processedRows} 筆，失敗 {$fail} 筆";

            // 建構詳細摘要
            $summary = [
                'processed_rows' => $processedRows,
                'total_orders' => $totalOrders,
                'carpool_sync' => $carpoolSync,
                'failed_rows' => $fail,
            ];

            return redirect()->route('orders.index')->with([
                'success' => $successMessage,
                'batch_update_summary' => $summary,
                'import_errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('批量更新匯入失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('orders.index')->with('error', '批量更新失敗：'.$e->getMessage());
        }
    }
}
