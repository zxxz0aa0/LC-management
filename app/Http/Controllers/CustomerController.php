<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Exports\CustomerTemplateExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\ImportSession;
use App\Services\CustomerImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = collect(); // 預設為空集合
        $hasSearched = false;
        $searchError = null;

        // 檢查是否有任何搜尋條件
        if ($request->filled('keyword') || $request->filled('referral_date') || $request->filled('county_care') || $request->has('status') || $request->filled('created_start') || $request->filled('created_end')) {
            $hasSearched = true;
            $query = Customer::query();

            // 關鍵字搜尋
            if ($request->filled('keyword')) {
                $keyword = trim($request->keyword); // 去除前後空格

                if (strlen($keyword) >= 1) {
                    // 關鍵字有效，執行搜尋
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('id_number', 'like', "%{$keyword}%")
                            ->orWhereJsonContains('phone_number', $keyword);
                    });
                } else {
                    // 只有空格或空字串
                    $searchError = '請輸入至少一個有效字元進行搜尋';
                    $customers = collect(); // 確保為空結果

                    return view('customers.index', compact('customers', 'hasSearched', 'searchError'));
                }
            }

            // 照會日期搜尋
            if ($request->filled('referral_date')) {
                $query->whereDate('referral_date', $request->referral_date);
            }

            // 建立時間區間搜尋
            if ($request->filled('created_start') && $request->filled('created_end')) {
                $startDate = \Carbon\Carbon::parse($request->created_start);
                $endDate = \Carbon\Carbon::parse($request->created_end);

                // 驗證時間範圍合理性
                if ($endDate->lt($startDate)) {
                    $searchError = '結束時間必須大於或等於開始時間';
                    $customers = collect();

                    return view('customers.index', compact('customers', 'hasSearched', 'searchError'));
                }

                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // 個案來源搜尋
            if ($request->filled('county_care')) {
                $query->where('county_care', $request->county_care);
            }

            // 個案狀態搜尋（只有在提交表單時才套用預設值）
            if ($request->has('status')) {
                $status = $request->input('status') ?: '開案中'; // 空字串時使用預設值
                $query->where('status', $status);
            }

            $customers = $query->latest()->paginate(15)->appends($request->except('page'));
        }

        return view('customers.index', compact('customers', 'hasSearched', 'searchError'));
    }

    public function create(Request $request)
    {
        return view('customers.create_customer', [
            'return_to' => $request->get('return_to'),
            'search_params' => $request->only(['keyword', 'start_date', 'end_date', 'customer_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20|unique:customers,id_number',
            'birthday' => 'nullable|date',
            'email' => 'nullable|email',
            'phone_number' => 'required|string',
            'addresses' => 'required|string',
            'referral_date' => 'required|date',
            'status' => 'required|in:開案中,暫停中,已結案',
        ]);

        // 檢查地址格式（每一筆都需包含"市"與"區"）
        // 使用智能分割：忽略括號內的逗號（如經緯度座標）
        $addressArray = array_map('trim', preg_split('/,(?![^()]*\))/', $validated['addresses']));
        $addressArray = array_filter($addressArray); // 過濾空字串
        foreach ($addressArray as $address) {
            if (! preg_match('/市.+區/', $address)) {
                return back()->withErrors(['addresses' => '每筆地址必須包含「市」與「區」'])->withInput();
            }
        }

        $phones = array_map('trim', explode(',', $validated['phone_number']));

        Customer::create([
            'name' => $validated['name'],
            'id_number' => $validated['id_number'],
            'birthday' => $validated['birthday'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $phones,
            'addresses' => $addressArray,
            'contact_person' => $request->contact_person,
            'contact_phone' => $request->contact_phone,
            'contact_relationship' => $request->contact_relationship,
            'gender' => $request->gender,
            'wheelchair' => $request->wheelchair,
            'stair_climbing_machine' => $request->stair_climbing_machine,
            'ride_sharing' => $request->ride_sharing,
            'identity' => $request->identity,
            'referral_date' => $validated['referral_date'],
            'note' => $request->note,
            'a_mechanism' => $request->a_mechanism,
            'a_manager' => $request->a_manager,
            'special_status' => $request->special_status,
            'county_care' => $request->county_care,
            'service_company' => $request->service_company,
            'status' => $validated['status'],
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        // 根據 return_to 參數決定返回位置
        if ($request->get('return_to') === 'orders') {
            $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'customer_id']);

            return redirect()->route('orders.index', $searchParams)->with('success', '客戶已建立');
        }

        return redirect()->route('customers.index')->with('success', '客戶已建立');
    }

    public function edit(Request $request, Customer $customer)
    {
        return view('customers.edit', [
            'customer' => $customer,
            'return_to' => $request->get('return_to'),
            'search_params' => $request->only(['keyword', 'start_date', 'end_date', 'customer_id']),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20|unique:customers,id_number,'.$customer->id,
            'email' => 'nullable|email',
            'phone_number' => 'required|string',
            'addresses' => 'required|string',
            'status' => 'required|in:開案中,暫停中,已結案',
        ]);

        // 使用智能分割：忽略括號內的逗號（如經緯度座標）
        $addressArray = array_map('trim', preg_split('/,(?![^()]*\))/', $validated['addresses']));
        $addressArray = array_filter($addressArray); // 過濾空字串
        foreach ($addressArray as $address) {
            if (! preg_match('/市.+區/', $address)) {
                return back()->withErrors(['addresses' => '每筆地址必須包含「市」與「區」'])->withInput();
            }
        }

        $phones = array_map('trim', explode(',', $validated['phone_number']));

        $customer->update([
            'name' => $validated['name'],
            'id_number' => $validated['id_number'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $phones,
            'addresses' => $addressArray,
            'contact_person' => $request->contact_person,
            'contact_phone' => $request->contact_phone,
            'contact_relationship' => $request->contact_relationship,
            'birthday' => $request->birthday,
            'gender' => $request->gender,
            'wheelchair' => $request->wheelchair,
            'stair_climbing_machine' => $request->stair_climbing_machine,
            'ride_sharing' => $request->ride_sharing,
            'identity' => $request->identity,
            'note' => $request->note,
            'a_mechanism' => $request->a_mechanism,
            'a_manager' => $request->a_manager,
            'special_status' => $request->special_status,
            'county_care' => $request->county_care,
            'service_company' => $request->service_company,
            'status' => $validated['status'],
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        // 根據 return_to 參數決定返回位置
        if ($request->get('return_to') === 'orders') {
            $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'customer_id']);

            return redirect()->route('orders.index', $searchParams)->with('success', '客戶已更新');
        }

        // 保留搜尋關鍵字
        $redirectParams = [];
        if ($request->filled('keyword')) {
            $redirectParams['keyword'] = $request->input('keyword');
        }

        return redirect()->route('customers.index', $redirectParams)->with('success', '客戶已更新');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', '客戶已刪除');
    }

    // 匯出 Excel
    public function export(Request $request)
    {
        return Excel::download(new CustomersExport($request), 'customers.xlsx');
    }

    /**
     * 處理客戶匯入
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 最大50MB
        ]);

        try {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            Log::info('開始新客戶匯入', [
                'filename' => $filename,
                'file_size' => $fileSize,
                'user_id' => auth()->id(),
            ]);

            // 儲存檔案
            $filePath = $file->store('imports', 'local');

            // 估算行數
            $totalRows = $this->estimateRowCount($file);

            // 建立匯入會話
            $sessionId = Str::uuid()->toString();
            $session = ImportSession::create([
                'session_id' => $sessionId,
                'type' => 'customers',
                'filename' => $filename,
                'file_path' => $filePath,
                'total_rows' => $totalRows,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // 判斷匯入方式：小於1000筆直接處理，否則佇列處理
            if ($totalRows < 1000) {
                return $this->processImportDirectly($session, $filePath);
            } else {
                return $this->processImportWithQueue($session, $filePath);
            }

        } catch (\Exception $e) {
            Log::error('客戶匯入失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'file' => '匯入失敗：'.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * 佇列匯入處理
     */
    public function queuedImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 最大50MB
        ]);

        try {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();

            Log::info('開始客戶佇列匯入', [
                'filename' => $filename,
                'file_size' => $file->getSize(),
                'user_id' => auth()->id(),
            ]);

            // 儲存檔案
            $filePath = $file->store('imports', 'local');

            // 預先讀取檔案計算總行數
            $rowCounter = new \App\Imports\RowCountImport;
            Excel::import($rowCounter, storage_path('app/'.$filePath));
            $totalRows = $rowCounter->getRowCount();

            // 生成會話ID
            $sessionId = (string) Str::uuid();

            // 建立匯入會話記錄
            $session = ImportSession::create([
                'session_id' => $sessionId,
                'type' => 'customers',
                'filename' => $filename,
                'file_path' => $filePath,
                'total_rows' => $totalRows,
                'processed_rows' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'error_messages' => [],
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // 立即重定向到進度頁面，讓用戶看到進度監控介面
            // 使用同步處理，但先重導向再執行匯入
            return redirect()->route('customers.import.progress', ['sessionId' => $sessionId])
                ->with('success', "匯入已開始處理，總共 {$totalRows} 筆資料。頁面將自動更新進度。");

        } catch (\Exception $e) {
            Log::error('客戶佇列匯入失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'file' => '佇列匯入失敗：'.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * 直接處理匯入（小量資料）
     */
    private function processImportDirectly(ImportSession $session, string $filePath): \Illuminate\Http\RedirectResponse
    {
        try {
            $importService = new CustomerImportService;
            $importService->setImportSession($session);

            // 讀取並處理檔案 (修復：使用 session_id 而不是 id)
            $data = Excel::toCollection(new CustomerImport($session->session_id), storage_path('app/'.$filePath))->first();
            $result = $importService->processImport($data);

            // 清理檔案
            Storage::delete($filePath);

            $message = "匯入完成！成功：{$result['success']} 筆，錯誤：{$result['errors']} 筆";

            return redirect()->route('customers.index')->with([
                'success' => $message,
                'import_errors' => $result['error_messages'],
            ]);

        } catch (\Exception $e) {
            $session->update([
                'status' => 'failed',
                'error_messages' => ['直接匯入失敗：'.$e->getMessage()],
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * 佇列處理匯入（大量資料）
     */
    private function processImportWithQueue(ImportSession $session, string $filePath): \Illuminate\Http\RedirectResponse
    {
        // 加入佇列
        Excel::queueImport(new CustomerImport($session->id), storage_path('app/'.$filePath));

        Log::info('客戶匯入已加入佇列', [
            'session_id' => $session->session_id,
            'total_rows' => $session->total_rows,
        ]);

        return redirect()->route('customers.import.progress', ['sessionId' => $session->session_id])
            ->with('success', "大量匯入已開始處理，預計 {$session->total_rows} 筆資料。請監控進度。");
    }

    /**
     * 估算 Excel 檔案行數
     */
    private function estimateRowCount($file): int
    {
        try {
            $tempPath = $file->store('temp', 'local');
            $fullPath = storage_path('app/'.$tempPath);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            $actualDataRows = 0;
            for ($row = 2; $row <= $highestRow; $row++) {
                $hasData = false;
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cellValue = $worksheet->getCell($col.$row)->getValue();
                    if (! empty(trim((string) $cellValue))) {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $actualDataRows++;
                }
            }

            unset($spreadsheet, $worksheet);
            Storage::delete(str_replace(storage_path('app/'), '', $tempPath));

            return $actualDataRows;

        } catch (\Exception $e) {
            Log::warning('行數估算失敗，使用檔案大小估算', [
                'error' => $e->getMessage(),
                'file_size' => $file->getSize(),
            ]);

            return max(100, intval($file->getSize() / 800));
        }
    }

    /**
     * 匯入進度頁面
     */
    public function importProgress(string $sessionId)
    {
        $session = ImportSession::where('session_id', $sessionId)->firstOrFail();

        return view('customers.import-progress', compact('session'));
    }

    /**
     * 啟動匯入處理的 API 端點
     */
    public function startImportProcess(string $sessionId)
    {
        $session = ImportSession::where('session_id', $sessionId)->first();

        if (! $session) {
            return response()->json(['error' => '找不到匯入會話'], 404);
        }

        if ($session->status !== 'pending') {
            return response()->json(['error' => '匯入會話狀態不正確'], 400);
        }

        // 立即返回響應，然後在背景執行匯入
        if (function_exists('fastcgi_finish_request')) {
            // FastCGI 環境，先發送響應再執行匯入
            $response = response()->json(['message' => '匯入已開始']);

            // 發送響應後執行匯入
            fastcgi_finish_request();

            $this->executeImportProcess($session);

            return $response;
        } else {
            // 非 FastCGI 環境，延遲執行
            register_shutdown_function(function () use ($session) {
                $this->executeImportProcess($session);
            });

            return response()->json(['message' => '匯入已開始']);
        }
    }

    /**
     * 執行匯入處理
     */
    private function executeImportProcess(ImportSession $session)
    {
        try {
            // 設定執行環境
            ini_set('max_execution_time', 3600); // 1小時
            ini_set('memory_limit', '2G'); // 2GB記憶體
            set_time_limit(3600); // 1小時執行時間

            Log::info('開始執行客戶匯入處理', [
                'session_id' => $session->session_id,
                'filename' => $session->filename,
            ]);

            // 開始匯入處理（使用正確的 session_id UUID）
            Log::info('準備執行 Excel 匯入', [
                'session_id' => $session->session_id,
                'session_db_id' => $session->id,
                'file_path' => $session->file_path,
            ]);

            $import = new \App\Imports\CustomerImport($session->session_id);
            Excel::import($import, storage_path('app/'.$session->file_path));

            Log::info('客戶匯入處理完成', [
                'session_id' => $session->session_id,
                'total_rows' => $session->total_rows,
            ]);

        } catch (\Exception $e) {
            Log::error('背景匯入處理失敗', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 標記會話為失敗
            $session->update([
                'status' => 'failed',
                'error_messages' => ['匯入處理失敗：'.$e->getMessage()],
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * 匯入進度 API
     */
    public function getImportProgress(string $sessionId)
    {
        try {
            Log::debug('獲取匯入進度', [
                'session_id' => $sessionId,
                'request_time' => now()->toDateTimeString(),
            ]);

            $session = ImportSession::where('session_id', $sessionId)->first();

            if (! $session) {
                Log::warning('找不到匯入會話', ['session_id' => $sessionId]);

                return response()->json([
                    'error' => '找不到匯入會話',
                    'session_id' => $sessionId,
                    'timestamp' => now()->toISOString(),
                ], 404);
            }

            // 確保數據的完整性和合理性
            $responseData = [
                'session_id' => $session->session_id,
                'filename' => $session->filename ?? '',
                'total_rows' => max(0, (int) $session->total_rows),
                'processed_rows' => max(0, min((int) $session->processed_rows, (int) $session->total_rows)),
                'success_count' => max(0, (int) $session->success_count),
                'error_count' => max(0, (int) $session->error_count),
                'status' => $session->status ?? 'pending',
                'status_text' => $session->status_text ?? '未知狀態',
                'progress_percentage' => round(max(0, min(100, (float) $session->progress_percentage)), 2),
                'remaining_rows' => max(0, (int) $session->remaining_rows),
                'error_messages' => is_array($session->error_messages) ? $session->error_messages : [],
                'started_at' => $session->started_at?->format('Y-m-d H:i:s'),
                'completed_at' => $session->completed_at?->format('Y-m-d H:i:s'),
                'processing_time' => $session->processing_time ? (int) $session->processing_time : null,
                'last_updated' => now()->toISOString(),
            ];

            // 數據一致性檢查
            if ($responseData['processed_rows'] > $responseData['total_rows']) {
                $responseData['processed_rows'] = $responseData['total_rows'];
            }

            if ($responseData['processed_rows'] < $responseData['success_count'] + $responseData['error_count']) {
                Log::warning('數據不一致檢測', [
                    'session_id' => $sessionId,
                    'processed_rows' => $responseData['processed_rows'],
                    'success_count' => $responseData['success_count'],
                    'error_count' => $responseData['error_count'],
                ]);
            }

            Log::debug('匯入進度回傳', [
                'session_id' => $sessionId,
                'status' => $responseData['status'],
                'progress' => $responseData['progress_percentage'],
                'processed' => $responseData['processed_rows'],
                'total' => $responseData['total_rows'],
            ]);

            return response()->json($responseData)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('獲取匯入進度失敗', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => '系統錯誤，無法獲取進度',
                'message' => config('app.debug') ? $e->getMessage() : '請稍後再試',
                'session_id' => $sessionId,
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! empty($ids)) {
            Customer::whereIn('id', $ids)->delete();

            return redirect()->route('customers.index')->with('success', '已成功刪除選取的客戶');
        }

        return redirect()->route('customers.index')->with('error', '請先勾選要刪除的資料');
    }

    // 更新客戶備註
    public function updateNote(Request $request, Customer $customer)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $customer->update([
            'note' => $request->note,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => '備註已更新',
            'note' => $customer->note,
        ]);
    }

    // 通用欄位更新方法
    public function updateField(Request $request, Customer $customer)
    {
        $fieldName = $request->input('field_name');
        $value = $request->input('value');

        // 允許更新的欄位清單
        $allowedFields = [
            'note',
            'a_manager',
            'wheelchair',
            'stair_climbing_machine',
            'service_company',
        ];

        // 驗證欄位是否允許更新
        if (! in_array($fieldName, $allowedFields)) {
            return response()->json([
                'success' => false,
                'message' => '不允許更新該欄位',
            ], 422);
        }

        // 根據欄位進行驗證
        $rules = [
            'note' => 'nullable|string|max:1000',
            'a_manager' => 'nullable|string|max:255',
            'wheelchair' => 'nullable|in:是,否,未知',
            'stair_climbing_machine' => 'nullable|in:是,否,未知',
            'service_company' => 'nullable|string|max:255',
        ];

        $messages = [
            'wheelchair.in' => '輪椅欄位只能是「是」、「否」或「未知」',
            'stair_climbing_machine.in' => '爬梯機欄位只能是「是」、「否」或「未知」',
        ];

        $request->validate([$fieldName => $rules[$fieldName]], $messages);

        // 更新欄位
        $customer->update([
            $fieldName => $value,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => '欄位已更新',
            'value' => $customer->getAttribute($fieldName),
        ]);
    }

    // 共乘對象查詢
    public function carpoolSearch(Request $request)
    {
        $keyword = $request->input('keyword');

        $customers = Customer::where(function ($query) use ($keyword) {
            $query->where('name', 'like', "%{$keyword}%")
                ->orWhere('id_number', 'like', "%{$keyword}%")
                ->orWhereJsonContains('phone_number', $keyword);
        })->get(['name', 'id_number', 'phone_number', 'addresses', 'id']);

        // 將 phone_number 改為只取第一個號碼
        $customers = $customers->map(function ($customer) {
            $customer->phone_number = is_array($customer->phone_number)
                ? ($customer->phone_number[0] ?? '')
                : $customer->phone_number;

            return $customer;
        });

        return response()->json($customers);
    }

    /**
     * 下載客戶匯入範例檔案
     */
    public function downloadTemplate()
    {
        return Excel::download(new CustomerTemplateExport, '客戶匯入範例檔案.xlsx');
    }

    /**
     * 診斷佇列系統狀態
     */
    public function diagnosticQueue()
    {
        try {
            $diagnostics = [
                'timestamp' => now()->toDateTimeString(),
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'queue_driver' => config('queue.default'),
            ];

            // 檢查佇列資料表
            $diagnostics['queue_stats'] = [
                'pending_jobs' => \DB::table('jobs')->count(),
                'failed_jobs' => \DB::table('failed_jobs')->count(),
                'jobs_details' => \DB::table('jobs')->get(['id', 'queue', 'attempts', 'created_at', 'available_at']),
            ];

            // 檢查匯入進度
            $recentImports = ImportSession::latest()->take(5)->get([
                'session_id', 'type', 'status', 'total_rows', 'processed_rows',
                'success_count', 'error_count', 'started_at', 'completed_at', 'created_at',
            ]);
            $diagnostics['recent_imports'] = $recentImports;

            // 檢查系統狀態
            $diagnostics['system_info'] = [
                'os' => PHP_OS,
                'working_directory' => getcwd(),
                'php_binary' => PHP_BINARY,
                'artisan_path' => base_path('artisan'),
                'storage_path' => storage_path(),
                'current_memory_usage' => memory_get_usage(true) / 1024 / 1024 .' MB',
            ];

            // 檢查必要的目錄和檔案
            $diagnostics['file_checks'] = [
                'artisan_exists' => file_exists(base_path('artisan')),
                'storage_writable' => is_writable(storage_path()),
                'imports_dir_exists' => is_dir(storage_path('app/imports')),
                'imports_dir_writable' => is_writable(storage_path('app/imports')),
            ];

            \Log::info('佇列診斷執行', $diagnostics);

            return response()->json([
                'success' => true,
                'diagnostics' => $diagnostics,
            ]);

        } catch (\Exception $e) {
            \Log::error('佇列診斷失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '診斷失敗：'.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 手動執行單一佇列任務（用於測試）
     */
    public function runSingleJob()
    {
        try {
            $job = \DB::table('jobs')->first();
            if (! $job) {
                return response()->json([
                    'success' => false,
                    'message' => '沒有找到待執行的佇列任務',
                ]);
            }

            \Log::info('手動執行佇列任務', [
                'job_id' => $job->id,
                'queue' => $job->queue,
                'attempts' => $job->attempts,
            ]);

            // 使用 Artisan 命令直接處理一個任務
            \Artisan::call('queue:work', [
                '--once' => true,
                '--timeout' => 7200,
                '--memory' => 2048,
                '--verbose' => true,
            ]);

            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => '佇列任務執行完成',
                'artisan_output' => $output,
                'remaining_jobs' => \DB::table('jobs')->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('手動執行佇列任務失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '執行失敗：'.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
